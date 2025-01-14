# form2mail

This is a simple PHP script that uses the built-in `mail` function to send a email based on a form that can be used
within a static site generator.

## Configuration

Rename the `config.sample.php` file to `config.php`and adapt the three config parameters in the map as needed

Simply send a form using `POST` to the folder, which includes the `index.php` file and you should be receiving emails.

Required form fields:
 - `email`: Used to set the Reply-to field

Have fun

## Compatibility
PHP: 8.3