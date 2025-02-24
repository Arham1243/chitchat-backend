# Chitchat Backend

## 🚀 Quick Start

Follow the steps below to set up and run the backend locally:

---

### Dependencies

Ensure the following Dependencies are installed on your system:

- **[PHP]** (v8.2+)
- **[Node.js]** (v20.16+)
- **[Git]**

---

### Installation

1. **Clone the Repository**:

    ```bash
    git clone https://github.com/Arham1243/chitchat-backend.git
    cd chitchat-backend

    ```

2. **Install PHP Dependencies:**:

    ```bash
    composer install
    ```

3. **Install Node.js Dependencies:**:

    ```bash
    npm install
    ```

4. **Configure Husky for Git Hooks:**:

    ```bash
    npm run configure-husky
    ```

5. **Set Up Environment Variables:**:

    ```bash
    cp .env.example .env
    ```

6. **Run Database Migrations:**:

    ```bash
    php artisan migrate
    ```

7. **Run the Queue Worker:**:

    ```bash
    php artisan queue:work
    ```

8. **Start the Reverb Server:**:

    ```bash
    php artisan reverb:start
    ```

9. **Serve the Project:**:

    ```bash
    php artisan serve
    ```

10. **Access the Application:**:

    ```bash
    http://127.0.0.1:8000
    ```