# Ini adalah project untuk Backend Test dari PT. Global Inovasi Gemilang.

<b>Uttar Pradesh Nahendra</b>  
085884885197  
uttarpn88@gmail.com

Ini adalah project untuk backend test, dibuat menggunakan Laravel.

## ERD

<h4>One to Many Companies to Users</h4>

![Logo](/public/assets/backed-test.drawio.png)

## Installation

Clone Git Project

```bash
  git clone https://github.com/abanguttar/backend-test.git
  cd backend-test
```

Install Composer

```bash
composer install
```

Copy .env and run migration

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

buka .env file pastikan anda setup email karena akan ada send email atau bisa gunakan konfigurasi saya

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=uttarpn88@gmail.com
MAIL_PASSWORD=xskidnxxpxbayuxf
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="mail@example.com"
MAIL_FROM_NAME="Paketur"
```

Run

```bash
php artisan serve
```

## API Reference

Berikut adalah daftar endpoint yang tersedia dalam project ini.

## Daftar Endpoint

Daftar Endpoint

-   Authentication

    -   Login
    -   Logout
    -   Reset Password
    -   Me / Current User

-   Company

    -   Create Company
    -   Get Company Detail by Id
    -   Update Company
    -   Delete Company
    -   Get Company List

-   Manager

    -   Create Manager
    -   Get Self Manager Detail
    -   Get Manager Detail by Id
    -   Update Manager
    -   Self Update Manager
    -   Delete Manager
    -   Get Manager List

-   Employee
    -   Create Employee
    -   Get Employee List
    -   Get Self Employee Detail
    -   Get Employee Detail by Id
    -   Update Employee
    -   Self Update Employee
    -   Delete Employee

####

<h1>Authentication</h1>

<h2>Login</h2>

```http
  POST /api/login
```

| Parameter  | Type     | Description                     |
| :--------- | :------- | :------------------------------ |
| `email`    | `string` | **Required**. Email pengguna    |
| `password` | `string` | **Required**. Password pengguna |

#### Authorization

Tidak diperlukan

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzM2MDczNzA3LCJleHAiOjE3MzYwNzczMDcsIm5iZiI6MTczNjA3MzcwNywianRpIjoiUG0wc2ZjZWlFbjBQNDBpUyIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.R2SOq3a288MOouF5QQfW-XVavZIWoKxy7l4_G6ZC1Mc",
    "token_type": "bearer",
    "expires_in": 3600,
    "data": {
        "email": "superadmin@superadmin.com",
        "name": "Super Admin"
    }
}
```

<h4>Error Response 400 Bad Request</h4>

<h6>Email atau Password salah</h6>

```json
{
    {
    "success": false,
    "errors": "email dan password salah!"
}
}
```

<h6>Email dan Password Kosong</h6>

```json
{
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

<br>
<br>
<h2>Logout</h2>

```http
  DELETE /api/logout
```

<h4>Deskripsi</h4>
<h6>Logout pengguna dengan menghapus token.</h6>

#### Authorization

Header:

```json
Authorization: Bearer <token>
```

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "success": true,
    "message": "Berhasil logout"
}
```

<br>
<br>
<h2>Reset Password</h2>

```http
  POST /api/password/reset?token={token}
```

| Parameter          | Type     | Description                                |
| :----------------- | :------- | :----------------------------------------- |
| `password`         | `string` | **Required**. Password pengguna            |
| `password_confirm` | `string` | **Required**. Konfirmasi Password pengguna |

#### Authorization

Header:

```json
Authorization: Bearer <token>
```

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "success": true,
    "message": "Berhasil mengubah password"
}
```

<h4>Error Response 400 Bad Request</h4>

<h6>Password dan Konfirmasi Password tidak sama</h6>

```json
{
    "errors": {
        "password_confirm": ["The password confirm field must match password."]
    }
}
```

<h6>Password dan Konfirmasi Password Kosong</h6>

```json
{
    "errors": {
        "password": ["The password field is required."],
        "password_confirm": ["The password confirm field is required."]
    }
}
```

<h6>Token tidak ada</h6>

```json
{
    "errors": {
        "token": ["The token field is required."]
    }
}
```

<br>
<br>
<h2>Me / Current User</h2>

```http
  GET /api/me
```

#### Authorization

Header:

```json
Authorization: Bearer <token>
```

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "id": 1,
    "company_id": 1,
    "name": "User Name",
    "email": "user@email.com",
    "role": "manager",
    "phone": 085884885197,
    "address": null,
    "token": null,
    "deleted_at": null,
    "created_at": "2025-01-05T10:18:31.000000Z",
    "updated_at": "2025-01-05T10:18:31.000000Z"
}
```

<h4>Error Response 401 Unauthorized</h4>

<br>
<br>
<br>

<h1>Company</h1>

<h2>Create Company</h2>

```http
  POST  /api/companies
```

| Parameter | Type      | Description                  |
| :-------- | :-------- | :--------------------------- |
| `email`   | `string`  | **Required**. Email pengguna |
| `name`    | `string`  | **Required**. Nama pengguna  |
| `phone`   | `integer` | **Required**. No Hp pengguna |

#### Authorization

Header:

```json
Authorization: Bearer <token>
```

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "success": true,
    "data": {
        "email": "uttarpn88@gmail.com",
        "name": "Uttar Pradesh",
        "phone": "085884885197"
    }
}
```

<h4>Check email inbox, disana akan ada link untuk mereset password (akun manager)</h4>

![Logo](/public/assets/screenshoot-email.jpeg)

<h4>Error Response 400 Bad Request</h4>

<h6>Email, name, phone kosong</h6>

```json
{
    "errors": {
        "name": [
            0 "The name field is required.",
        ],
        "email": [
            0 "The email field must be a valid email address."
        ],
        "phone": [
            0 "The phone field must be a number.",
            1 "The phone field must not have more than 20 digits."
        ]
    }

}
```

<h6>Email telah digunakan</h6>

```json
{
    "errors": {

        "email": [
             0 "The email has already been taken."
        ],
    }
}
```

<h4>Error Response 403 Unauthorized</h4>
<h6>Role bukan superadmin</h6>

```json
{
    "success": false,
    "message": "Tidak memiliki akses"
}
```

<br>
<br>

<h2>Get Company Detail by Id</h2>

```http
  GET  /api/companies/{id}
```

#### Authorization

Header:

```json
Authorization: Bearer <token>
```

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Uttar Pradesh",
        "email": "uttarpn88@gmail.com",
        "phone": 85884885197,
        "deleted_at": null,
        "created_at": "2025-01-05T11:37:27.000000Z",
        "updated_at": "2025-01-05T11:37:27.000000Z"
    }
}
```

<h4>Error Response 403 Unauthorized</h4>
<h6>Role bukan superadmin</h6>

```json
{
    "success": false,
    "message": "Tidak memiliki akses"
}
```

<br>
<br>

<h2>Update Company</h2>

```http
  PUT  /api/companies/{id}
```

| Parameter | Type      | Description                  |
| :-------- | :-------- | :--------------------------- |
| `email`   | `string`  | **Required**. Email pengguna |
| `name`    | `string`  | **Required**. Nama pengguna  |
| `phone`   | `integer` | **Required**. No Hp pengguna |

#### Authorization

Header:

```json
Authorization: Bearer <token>
```

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Update Name",
        "email": "update@gmail.com",
        "phone": 98709234,
        "deleted_at": null,
        "created_at": "2025-01-05T12:04:22.000000Z",
        "updated_at": "2025-01-05T12:04:22.000000Z"
    }
}
```

<h4>Error Response 403 Unauthorized</h4>
<h6>Role bukan superadmin</h6>

```json
{
    "success": false,
    "message": "Tidak memiliki akses"
}
```

<br>
<br>

<h2>Delete Company</h2>

```http
  DELETE  /api/companies/{id}
```

#### Authorization

Header:

```json
Authorization: Bearer <token>
```

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "success": true,
    "message": "Berhasil menghapus data"
}
```

<h4>Error Response 403 Unauthorized</h4>
<h6>Role bukan superadmin</h6>

```json
{
    "success": false,
    "message": "Tidak memiliki akses"
}
```

<br>
<br>

<h2>Get Company List</h2>

```http
  GET  /api/companies?name={name}&sortBy={id}&sortDir={desc}&page={page}
```

| Parameter | Type      | Description                                                      |
| :-------- | :-------- | :--------------------------------------------------------------- |
| `name`    | `string`  | _opsional_. Nama perusahaan                                      |
| `sortBy`  | `string`  | _opsional_. SortBy id, name, email, phone (default by id)        |
| `sortDir` | `string`  | _opsional_. SortDirection ascending, descending (default by asc) |
| `page`    | `integer` | _opsional_. page number (default 1)                              |

#### Authorization

Header:

```json
Authorization: Bearer <token>
```

#### Response

<h4>Success Response 200 OK</h4>

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "Mrs. Abbie Frami III",
                "email": "brittany12@example.org",
                "phone": 88715726,
                "deleted_at": null,
                "created_at": "2025-01-05T12:14:19.000000Z",
                "updated_at": "2025-01-05T12:14:19.000000Z"
            },
            {
                "id": 2,
                "name": "Camron Jacobs",
                "email": "kshlerin.lorna@example.net",
                "phone": 35313290,
                "deleted_at": null,
                "created_at": "2025-01-05T12:14:19.000000Z",
                "updated_at": "2025-01-05T12:14:19.000000Z"
            }
        ],
        "first_page_url": "http://localhost:8000/api/companies?page=1",
        "from": 1,
        "last_page": 6,
        "last_page_url": "http://localhost:8000/api/companies?page=6",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "http://localhost:8000/api/companies?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "http://localhost:8000/api/companies?page=2",
                "label": "2",
                "active": false
            },
            {
                "url": "http://localhost:8000/api/companies?page=2",
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "next_page_url": "http://localhost:8000/api/companies?page=2",
        "path": "http://localhost:8000/api/companies",
        "per_page": 20,
        "prev_page_url": null,
        "to": 20,
        "total": 101
    }
}
```

<h4>Error Response 404 Not Found</h4>
<h6>Nama perusahaan tidak ditemukan</h6>

```json
{
    "success": false,
    "message": "Data tidak ditemukan"
}
```

<h4>Error Response 403 Unauthorized</h4>
<h6>Role bukan superadmin</h6>

```json
{
    "success": false,
    "message": "Tidak memiliki akses"
}
```

<br>
<br>

<h4>Maaf saya tidak bisa menyelesaikan dokumentasi dengan lengkap</h4>
<h4>Namun ada beberapa validasi yang telah saya buat di controller dan dicek dengan unit test.</h4>

-   Role Superadmin

    -   Saat mengakses list manager, data manager dari semua perusahaan akan ditampilkan
    -   Saat mengakses list employee, data manager dari semua perusahaan akan ditampilkan

-   Role Manager
    -   Saat mengakses list manager, data manager hanya akan tampil dari perusahaan yang sama
    -   Saat mengakses list employee, data manager hanya akan tampil dari perusahaan yang sama
    -   Saat membuat, mengubah, atau menghapus data employee. Hanya bisa dilakukan untuk employee dengan perusahaan yang sama

<h3>Terima Kasih</h3>
