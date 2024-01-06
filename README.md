<img align="left" src="https://github.com/BlackyDrum/laravel-file-manager/assets/111639941/644643ac-ba5f-4568-bb46-ac7ff5c929f1" />

<br />

<img src="https://github.com/BlackyDrum/laravel-file-manager/assets/111639941/efa9be8f-8589-410f-9aa1-0353658144d0" width="400"></a><br /><br />

**Share, organize and manage files with ease**

<br />

[![Generic badge](https://img.shields.io/badge/Status-In_Development-orange.svg)](https://shields.io/) [![Generic badge](https://img.shields.io/badge/License-MIT-<COLOR>.svg)](https://shields.io/) 
 
<br />

<img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"> <img src="https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D"> <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white"> <img src="https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white">


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
7. **Install JavaScript dependencies:**
```
$ npm install
```
8. **Build the assets:**
```
$ npm run dev
```
9. **Start the development server:**
```
$ php artisan serve
```
10. **Visit http://localhost:8000 in your web browser to access the application.**


## Adjusting Maximum File Size
If you wish to modify the maximum file size allowed for uploads, navigate to your ``.env`` file and update the ``MAX_FILE_SIZE`` variable.
```
MAX_FILE_SIZE=1024000  # Set the maximum file size in bytes
```
**Note**: Ensure that you update the ``upload_max_filesize`` and ``post_max_size`` values in your ``php.ini`` file to match the desired maximum file size.

## License

This project is licensed under the [MIT license](https://opensource.org/licenses/MIT).
