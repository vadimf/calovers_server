<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Add Your Business';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin([
                    'id' => 'add-bussiness-form',
            ]); ?>
            
                <?= $form->field($model, 'category')->radioList([
                    'food' => 'Food and accessories', 
                    'veterinary' => 'Veterinary'
                ])?>

                <?= $form->field($model, 'name')->textInput(['autofocus' => true]) ?>
                
                <?= $form->field($model, 'address')->textInput() ?>
                
            	<div id="map" style="width: 100%;height: 300px;margin-bottom: 15px;"></div>
            	
            	<?= Html::activeHiddenInput($model, 'lat') ?>
            	
            	<?= Html::activeHiddenInput($model, 'lng') ?>
            	
                <?= $form->field($model, 'link')->textInput() ?>
                
                <?= $form->field($model, 'phone')->textInput() ?>
                
                <?= $form->field($model, 'open_hour')->textInput()->label('Working Hours') ?>
                
                <?= $form->field($model, 'description')->textarea() ?>

                <div class="form-group">
                    <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'submit-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>
var initMap = function () {
	var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 12,
        center: new google.maps.LatLng("42.353469793491", "-71.0595703125")
      });

    var geocoder = new google.maps.Geocoder;

    var setPosition = function (pointData) {

        if ( map.marker ) {
            map.marker.setMap(null);
        }
        var point = new google.maps.LatLng(pointData.latitude, pointData.longitude);

        map.marker = new google.maps.Marker({
            map: map,
            position: point,
            draggable: true,
        });

        google.maps.event.addListener(map.marker, 'dragend', function() {
            setPosition(this.getPosition());
        });

        map.panTo(point);

        jQuery('#business-lat').val(pointData.latitude);
        jQuery('#business-lng').val(pointData.longitude);
    };
    
    searchBarAutocomplete = new google.maps.places.Autocomplete(document.getElementById('business-address'));

    google.maps.event.addListener(searchBarAutocomplete, 'place_changed', function () {
        var place = this.getPlace();
        var placeGeometry = place.geometry;
        if ( placeGeometry )
        {
            var placeLocation = placeGeometry.location;
            setPosition({
            	latitude: placeLocation.lat(), 
            	longitude: placeLocation.lng()
            });
        }
    });

	google.maps.event.addListener(map, 'click', function ( click ) {
		console.log(click)
        setPosition({
            latitude: click.latLng.lat(),
            longitude: click.latLng.lng(),
        });

		geocoder.geocode({'location': click.latLng}, function(results, status) {
          	if (status === 'OK') {
                if (results[1]) {
                    jQuery('[name="Business[address]"]').val(results[1].formatted_address);
                } else {
                  	window.alert('No results found');
                }
			} else {
            	window.alert('Geocoder failed due to: ' + status);
          	}
        });
	});
};
</script>

<script src="//maps.googleapis.com/maps/api/js?key=AIzaSyC3gDjCO4h15Djt33Yir4Fm5kuLTe9GFpY&callback=initMap&libraries=places"></script>
