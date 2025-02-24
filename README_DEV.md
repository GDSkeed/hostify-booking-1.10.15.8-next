# WP plugin development

## Prerequisites

* PHP 7+
* PHP extenstions: `curl libxml mbstring mcrypt mysqli openssl pdo_mysql xml`
* Apache, Nginx or PHP Built-in web server
* MySQL 5+ or MariaDB server
* [Composer](https://getcomposer.org/)
* NodeJS & NPM

## IDE

Use [Visual Studio Code](https://code.visualstudio.com/)

VS Code addons:

* [phpcs](https://marketplace.visualstudio.com/items?itemName=ikappas.phpcs)
* [phpcbf](https://marketplace.visualstudio.com/items?itemName=persoderlind.vscode-phpcbf)

Check out files in `.vscode/settings.json` folder inside the project.

How to use [WordPress Stubs](https://github.com/GiacoCorsiglia/wordpress-stubs)

## Compile SASS

You can use any of your favorite methods or one of these:

* Method 1: VS Code tasks

  - `npm install -g node-sass less`
  - add this block to `.vscode/tasks.json` for the project:

  ```json
  {
      "label": "Sass Compile",
      "type": "shell",
      "command": "./sass-compile",
      "group": "build",
      "presentation": {
          "reveal": "silent"
      },
      "problemMatcher": [
          "$node-sass"
      ]
  },
  ```

  - add this block to `.vscode/settings.json`:

  ```json
  "triggerTaskOnSave.tasks": {
    "Sass Compile": [
      "**/src/**/*.scss",
    ]
  },
  ```

* Method 2: npm

  - `npm install`
  - `npm run sass` to compile
  - `npm run sass-watch` to watch for changes during development

## Resources

* [WP developer](https://developer.wordpress.org/)
* [Debugging](https://deliciousbrains.com/vs-code-wordpress/)
* Translations edit: [Poedit](https://poedit.net/)
