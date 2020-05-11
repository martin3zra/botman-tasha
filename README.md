# Tasha Expenser

`Tasha` is a chatbot application that has two primary responsibilities. First, be able to receive specific orders from a user and reply accordingly. Second, allow a user to log-in, hold a balance, and make transactions.

The chatbot may be run as a regular Laravel application or as a Docker container.

Please follow the steps below if you would like to play with `Tasha` a little.


## Run it with your local PHP

Make sure you have `PHP 7.x`, [Composer](https://getcomposer.org/) and `php-sqlite3` installed and set up in your local machine. Run the below commands in the order they appear.

```shell
# Install dependencies
composer install

# Create .env file for Laravel, the database file and set permissions.
cp .env.example .env \
    && touch ./database/database.sqlite \
    && chmod -R 777 database
```

Update the value of the `EXCHANGE_KEY` variable in the `.env` file with the one you obtained from [Fixer.io](https://fixer.io/).

Run the following command to set up Laravel.

```shell
# Setup Laravel to run Tasha
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
```


## Run with Docker

Update the value of the `EXCHANGE_KEY` variable in the `.env.example` file with the one you obtained from [Fixer.io](https://fixer.io/).

With Docker installed and running in your machine, run the following commands.


```shell
# Build image
docker build -t tasha:1.0.0 .

# Create and run a container name tasha.
docker run --name tasha -p 8090:80 -it -d tasha:1.0.0
```


## How use Tasha

The bot will show you a welcome message and guide you through the necessary steps to perform any operation that is supported.

![welocome](screenshots/welcome.png?raw=true)

After you signed up, log in so that you can perform money transactions.

1. Type one of the following trigger phrase to start interacting with `Tasha`: `GET_STARTED | hey | start`.
2. Type `register` to initiate the signup process flow, then follow the prompts to enter the necessary information required to create your account.
3. Type `authenticate` to initiate the login flow. This command will start a conversation to ask you questions about your account and help you with the authentication process.
4. Finally, a fallback message is displayed whenever you type an unknown command.
