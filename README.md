# Backend Server for Cat Lovers Mobile App – Node.js + TypeScript

This repository contains the backend server for a mobile app developed by **Globalbit**, designed for a global community of cat lovers.  
The app enables users to share content, track their pets, and connect through a social, location-based experience — powered by a secure, scalable API.

---

## 🐾 App Purpose

The backend supports features such as:
- 🐱 User registration and profile customization
- 📸 Upload and comment on cat photos
- 📍 Geo-tagging favorite places (vets, parks, shelters)
- ❤️ Like, follow, and message other users
- 🎯 Personalized content and activity feeds

---

## 🧰 Tech Stack

- **Language**: TypeScript (Node.js)
- **Framework**: Express.js
- **Database**: PostgreSQL / MongoDB
- **Storage**: AWS S3 for media
- **Authentication**: JWT + OAuth2 (Google/Apple)
- **Real-Time**: WebSocket or Socket.IO for chat and activity feed
- **Deployment**: Docker, GitHub Actions, scalable on AWS/GCP

---

## 🔒 Security & Performance

- Rate-limited APIs
- Role-based access control (admin, moderator, user)
- Secure image upload & validation
- GDPR-compliant user data management

---

## 🧩 Key Modules

- `/routes` – Auth, users, pets, posts, messages
- `/services` – Image handling, notifications, activity tracking
- `/models` – Database schema definitions
- `/sockets` – WebSocket events and channel handlers

---

## 🏗 Built by Globalbit

**Globalbit** is an award-winning software development company based in Israel, with extensive experience in building **social platforms**, **niche communities**, and **real-time mobile ecosystems**.

We serve over **200 million users** globally through platforms we’ve built across:
- 🏥 Health & Lifestyle
- 🛍️ Consumer Apps & Retail
- 📲 Mobile-first Social Experiences

---

## 📎 Getting Started

```bash
git clone https://github.com/globalbit/cat-lovers-backend.git
cd cat-lovers-backend
cp .env.example .env
npm install
npm run dev
Swagger API docs are available at /api/docs.


Whether it’s for pets, people, or global platforms — Globalbit builds mobile-first systems that scale and connect.

## 📞 Let’s Build Secure, Resilient Communication Together

Globalbit builds robust mobile platforms for challenging environments — from public safety to connected mobility.

📩 [info@globalbit.co.il](mailto:info@globalbit.co.il)  
🌐 [globalbit.co.il](https://globalbit.co.il)
