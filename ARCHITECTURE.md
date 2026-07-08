# 🏗️ Architecture

โครงสร้างระบบ (Architecture) ของโปรเจคนี้ออกแบบมาเพื่อให้มีการแยกส่วนการทำงาน (Separation of Concerns) อย่างชัดเจน ทำให้ง่ายต่อการจัดการ บำรุงรักษา และขยายสเกลในอนาคต

## แผนภาพโครงสร้างโดยรวม (System Flow)

```text
[ Frontend (Nuxt 3) ]
         │
         ▼ (HTTP Requests / JSON)
[ API (PHP Endpoints) ]
         │
         ▼
[ Service (Business Logic) ]
         │
         ▼
[ Repository (Data Access) ]
         │
         ▼ (SQL / PDO)
[ Database (MySQL) ]
```

## รายละเอียดแต่ละ Layer

### 1. Frontend (Presentation Layer)
- พัฒนาด้วย **Nuxt 3** และ **Vue 3**
- ทำหน้าที่แสดงผล UI ติดต่อกับผู้ใช้ และจัดการ State ต่างๆ ภายในแอปพลิเคชัน
- เรียกใช้งาน Backend ผ่าน API (โดยใช้ `$fetch` หรือ `useFetch` ของ Nuxt เท่านั้น)

### 2. API (Controller Layer)
- เป็นไฟล์ PHP ที่รับ Request จาก Frontend (เช่น GET, POST, PUT, DELETE)
- ทำหน้าที่ตรวจสอบ (Validate) ข้อมูลเบื้องต้น และส่งข้อมูลต่อไปยัง Service

### 3. Service (Business Logic Layer)
- เป็นหัวใจหลักในการประมวลผลเงื่อนไขและกฎเกณฑ์ทางธุรกิจ (Business Rules)
- หากมีการคำนวณ หรือเงื่อนไขที่ซับซ้อน จะถูกเขียนไว้ในชั้นนี้เพื่อไม่ให้ API หนักเกินไป

### 4. Repository (Data Access Layer)
- ทำหน้าที่ติดต่อกับฐานข้อมูลโดยตรง
- ซ่อนความซับซ้อนของคำสั่ง SQL (หรือ PDO) ไว้ในคลาส/ฟังก์ชันนี้
- ส่งต่อผลลัพธ์ที่เป็นชุดข้อมูลกลับไปยัง Service

### 5. Database (Data Layer)
- **MySQL 8** (ตั้งค่า Character Set เป็น `utf8mb4` เพื่อรองรับภาษาไทย 100%)
- เก็บข้อมูลทุกอย่างของระบบ (เช่น Users, Issues, Equipment)
