<img align="left" src="https://github.com/BlackyDrum/laravel-file-manager/assets/111639941/644643ac-ba5f-4568-bb46-ac7ff5c929f1" />

<br />

<img src="https://github.com/BlackyDrum/laravel-file-manager/assets/111639941/efa9be8f-8589-410f-9aa1-0353658144d0" width="400"></a><br /><br />

**Share, organize and manage files with ease**

<br />

[![Generic badge](https://img.shields.io/badge/Status-In_Development-orange.svg)](https://shields.io/) [![Generic badge](https://img.shields.io/badge/License-MIT-<COLOR>.svg)](https://shields.io/) 
 
<br />

<img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"> <img src="https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D"> <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white"> <img src="https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white">

---

## Features
- **User-friendly Interface:** Intuitive UI for easy navigation and file management.
- **File Upload/Download:** Quickly upload and download files with ease.
- **File Sharing:** Share files securely with customizable sharing options.
- **Security:** Built with Laravel best practices to ensure data security.


## Requirements
<ul>
    <li>PHP: ^8.3</li>
    <li>Node: ^20.10</li>
    <li>Composer: ^2.5</li>
</ul>

## Installation
**Follow these steps to get the Laravel File Manager up and running on your local machine:**
1. **Clone the repository:**
```
$ git clone https://github.com/BlackyDrum/laravel-file-manager.git
```
2. **Navigate to the project directory:**
```
$ cd laravel-file-manager
```
3. **Install the dependencies:**
```
$ composer install
```
4. **Create a copy of the .env.example file and rename it to .env. Update the necessary configuration values such as the Database credentials:**
```
$ cp .env.example .env
```
5. **Generate an application key:**
```
$ php artisan key:generate
```
6. **Run the database migrations:**
```
$ php artisan migrate
```
7. **Seed the database with data:**
```
$ php artisan db:seed --class=AppData
```
8. **Install JavaScript dependencies:**
```
$ npm install
```
9. **Build the assets:**
```
$ npm run dev
```
10. **Start the development server:**
```
$ php artisan serve
```
11. **Visit http://localhost:8000 in your web browser to access the application.**

## OAuth Authentication
To enable login with Google or GitHub, you need to create OAuth apps on their respective platforms and set the ``client ID``, ``client secret`` and ``client callback`` in the ``.env`` file.

## Email Configuration

Before using Laravel File Manager, it's important to configure the mail settings in your `.env` file. This is necessary for sending verification emails. Follow the steps below to set up the required mail fields:

1. Open the `.env` file located in the root directory of the project.

2. Locate the following fields related to mail configuration and update them with your email service provider's credentials:

```env
MAIL_MAILER=smtp
MAIL_HOST=your_mail_host
MAIL_PORT=your_mail_port
MAIL_USERNAME=your_mail_username
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=your_mail_encryption
MAIL_FROM_ADDRESS="your_mail_address"
```


## Adjusting Maximum File And Storage Size
If you wish to modify the maximum file and storage size allowed for users, navigate to your ``.env`` file and update the respective variables.
```env
MAX_FILE_SIZE=1024000  # Set the maximum file size in bytes
MAX_STORAGE_SIZE=100000000 # Set the maximum storage size per user in bytes
```
**Note**: Ensure that you update the ``upload_max_filesize`` and ``post_max_size`` values in your ``php.ini`` file to match the desired maximum file size.

## License

This project is licensed under the [MIT license](https://opensource.org/licenses/MIT).
