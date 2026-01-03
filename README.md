# 🛒 StandByMall Ecommerce Platform

Welcome to **StandByMall**, a modern, extensible PHP-based ecommerce framework designed for learning, customization, and real-world online store projects.

---

## 🚀 Features

- **User Authentication**: Registration, login, password reset, and user dashboard.
- **Product Management**: CRUD for products, categories, and inventory.
- **Shopping Cart**: Add, update, and remove items with a persistent cart.
- **Order Processing**: Checkout, order history, and order management.
- **Admin Panel**: Manage users, products, categories, and orders.
- **Reviews & Ratings**: Product reviews and star ratings.
- **Responsive Design**: Mobile-friendly UI with custom CSS.
- **Secure**: Input validation, prepared statements, and session management.
- **Extensible**: Modular MVC structure for easy feature addition.

---

## ⚡ Quick Start

1. **Clone the repository**
    ```bash
    git clone https://github.com/kameniarthur/ecommerce.git
    cd ecommerce
    ```

2. **Install dependencies**
    ```bash
    composer install
    ```

3. **Configure your environment**
    - Copy `config.php.example` to `config.php` and set your database credentials.

4. **Run migrations and seed data**
    ```bash
    php database/migrate.php
    php database/seeds/sample_data.sql
    ```

5. **Start the development server**
    ```bash
    php -S localhost:8000 -t public
    ```

6. **Visit your app**
    - Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## 🛠️ Development

- **Controllers**: `app/controller/`
- **Models**: `app/models/`
- **Views**: `app/views/`
- **Core**: `app/core/`
- **Helpers**: `app/helpers/`

---

## 🧑‍💻 Contributing

Pull requests are welcome! Please open an issue first to discuss your ideas or report bugs.

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Acknowledgements

- Inspired by Laravel, CodeIgniter, and other open-source PHP frameworks.
- Thanks to all contributors and the open-source community!

---

> **StandByMall** — Your starting point for building robust PHP ecommerce solutions.


