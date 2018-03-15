<?php 
namespace api\models;

use GuzzleHttp\Promise;

use yii\imagine\Image;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadImages extends Model
{
    const THUMBNAIL_WIDTH = 250;
    const THUMBNAIL_HEIGHT = 250;
    
    /**
     * @var UploadedFile[]
     */
    public $images;
    
    public $uploadPath;
    
    public $mimeTypes = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png'
    );
    
    public function rules()
    {
        return [
            [['images'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg', 'maxFiles' => 5],
        ];
    }
    
    public function upload()
    {
        if (!$this->validate()) {
            return false;
        }
        
        $awssdk = Yii::$app->awssdk->getAwsSdk();
        $client = $awssdk->createClient('S3');
        
        try {
            $client->headBucket([
                'Bucket' => 'catslovers1'
            ]);
        } catch (\Aws\S3\Exception\S3Exception $exp) {
            $client->createBucket(array('Bucket' => 'catslovers'));
        }
        
        $promises = [];
        $promisesThumb = [];
        foreach ($this->images as $image) {
            $uploadParam = [
                'Bucket'       => 'catslovers',
                'Key'          => $this->uploadPath.$image->name,
                'SourceFile'   => $image->tempName,
                'ContentType'  => $image->type,
                'ACL'          => 'public-read',
            ];
            $promises[] = $client->putObjectAsync($uploadParam);
            
            $thumbnail = $this->_getThumbnail($image);
            $thumbnailName = $this->_getThumbnailFileName($image->name);
            
            $uploadParam = [
                'Bucket'       => 'catslovers',
                'Key'          => $this->uploadPath.$thumbnailName,
                'Body'   => $thumbnail,
                'ContentType'  => $image->type,
                'ACL'          => 'public-read',
            ];
            $promisesThumb[] = $client->putObjectAsync($uploadParam);
        }
        
        $results = Promise\unwrap($promises);
        
        $urls = [];
        foreach ($results as $result) {
           $urls[] = array(
               'photo' => $result->offsetGet('ObjectURL')
           );
        }
        
        $results = Promise\unwrap($promisesThumb);
        
        foreach ($results as $i => $result) {
           $urls[$i]['thumbnail'] = $result->offsetGet('ObjectURL');
        }
        
        return $urls;
    }
    
    private function _getThumbnail($image)
    {
        return Image::thumbnail($image->tempName, self::THUMBNAIL_WIDTH, self::THUMBNAIL_HEIGHT)
            ->get($this->mimeTypes[$image->type]);
    } // end _getThumbnail
    
    private function _getThumbnailFileName($origName)
    {
        $pieces = explode('.', $origName);
        
        $size = '_'.self::THUMBNAIL_WIDTH.'x'.self::THUMBNAIL_HEIGHT.'_';
        array_splice($pieces, count($pieces) - 1, 0, $size);
        
        return implode('.', $pieces);
    } // end _getThumbnailFileName
}
