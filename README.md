# Web Technology Lab Test 3 

## By Ching Jie Kai A21MJ5037

This project is a simple CRUD application using Slim PHP for the backend and Vue.js for the frontend. It allows users to manage a list of users with basic operations like creating, reading, updating, and deleting user records.

## Repository Link

https://github.com/JKAIC1009/WebTechLab3.git

## Project Structure

```
webtechlab3/
├── backend/
│   ├── config.php
│   ├── index.php
│   └── vendor/
├── frontend/
│   ├── public/
│   ├── src/
│   │   ├── App.vue
│   │   ├── main.js
│   │   └── store/
│   │       └── index.js
│   ├── package.json
│   └── vue.config.js
└── README.md
```

## Requirements

- PHP 7.4 or higher
- Composer
- Node.js and npm
- MySQL database

## Backend Setup

1. Navigate to the `backend` directory:
   ```
   cd backend
   ```

2. Install dependencies using Composer:
   ```
   composer install
   ```

3. Configure your database connection in `config.php`.

4. Import the SQL file (not provided in the given code snippets) to set up your database schema.

5. Start your PHP server:
   ```
   php -S localhost:8088
   ```

## Frontend Setup

1. Navigate to the `frontend` directory:
   ```
   cd frontend
   ```

2. Install dependencies:
   ```
   npm install
   ```

3. Start the development server:
   ```
   npm run serve
   ```

## Usage

1. Click the link provided after run `npm run serve` to access the frontend application.
2. Use the form to create new users or update existing ones.
3. View the list of users on the right side of the page.
4. Click "Choose" to select a user for editing.
5. Click "Remove" to delete a user.

## API Endpoints

- GET `/users`: Fetch all users
- GET `/users/{id}`: Fetch a specific user
- POST `/users`: Create a new user
- PUT `/users/{id}`: Update an existing user
- DELETE `/users/{id}`: Delete a user

## Technologies Used

- Backend: Slim PHP 4
- Frontend: Vue.js 3 with Vuex
- Database: MySQL
- Styling: Bootstrap 5
