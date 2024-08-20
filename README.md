# Ray 📧

**Ray** is a PHP-based project designed to securely fetch, store, and manage email messages from an IMAP server into a PostgreSQL database. Named in honor of Raymond Tomlinson, the pioneer of email, this project ensures efficient email processing with a focus on deduplication and security.

## Features ✨

- **Secure IMAP Connection**: Fetch emails using secure IMAP connections.
- **Deduplication**: Avoid storing duplicate emails by checking content hashes before insertion.
- **Configurable**: Easy-to-manage configuration files for server and database settings.
- **Error Handling**: Robust error management to ensure seamless email processing.

## Setup 🚀

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/yourusername/ray.git
 ```
 ```

2. **Configure the Project**:
    Edit config.php for IMAP server settings.
    Edit db_config.php for database connection details.

3. **Create the Database Table**:
   - Run the following SQL to create the `tb_emails` table:
     ```sql
     CREATE TABLE tb_emails (
         id SERIAL PRIMARY KEY,
         subject TEXT NOT NULL,
         from_email VARCHAR(255) NOT NULL,
         message TEXT NOT NULL,
         ip_address VARCHAR(45),
         timestamp TIMESTAMP NOT NULL,
         hash VARCHAR(64) NOT NULL
     );
     ```
4. **Run the Script**:
   - Execute the PHP script to fetch and store emails.
  ```bash
   php ray.php
```
## Acknowledgements 🙏

Ray is named after **Raymond Tomlinson**, the inventor of email, whose innovation continues to be the backbone of digital communication.

## License 📜

This project is licensed under the MIT License.
