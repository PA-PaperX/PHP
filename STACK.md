# 🛠️ Tech Stack

ระบบนี้ใช้เทคโนโลยีที่ทันสมัยและเป็นมาตรฐาน เพื่อความรวดเร็วในการพัฒนาและประสิทธิภาพที่ดีที่สุด

## 🌐 Frontend (หน้าบ้าน)
- **Framework:** Nuxt 4 (SSR/SSG Support)
- **Core:** Vue 3 (บังคับใช้ Composition API ด้วย `<script setup lang="ts">`)
- **Language:** TypeScript
- **Styling:** Tailwind CSS
- **UI Components:** Nuxt UI (`@nuxt/ui`) - ใช้ Component พื้นฐานเช่น `UButton`, `UInput`, `UIcon`
- **Icons:** Heroicons (`i-heroicons-*` ผ่าน Nuxt UI)
- **Animations:** `@vueuse/motion`
- **Utilities:** VueUse (`@vueuse/nuxt`)

## ⚙️ Backend (หลังบ้าน)
- **Language:** Native PHP (PHP 8.x)
- **Architecture:** API -> Service -> Repository
- **Database Connection:** PDO (PHP Data Objects) พร้อมการทำ Prepared Statements เสมอเพื่อป้องกัน SQL Injection

## 🗄️ Database (ฐานข้อมูล)
- **DBMS:** MySQL 8
- **Encoding/Collation:** `utf8mb4` / `utf8mb4_unicode_ci` (เพื่อรองรับภาษาไทยและ Emoji อย่างสมบูรณ์)

## 💻 Development & Deployment (เครื่องมือพัฒนา)
- **Local Server:** XAMPP (สำหรับเซิร์ฟเวอร์ PHP และ MySQL ในเครื่องนักพัฒนา)
- **Container (Optional):** Docker / Docker Compose (สำหรับจำลอง Environment)
- **Version Control:** Git & GitHub
