## Tasha Expenser

## Usages

If you would like to run and play with `Tasha`. Just execute the following command.
```shell
cp .env.example .env \
    && touch ./database/database.sqlite \
    && chmod -R 777 database

php artisan key:generate
php artisan storage:link
php artisan migrate --seed
```
To create the database structure and seed initial data please run the above command.
```shell
php artisan migrate --seed
```

## Docker steps
Build image
```shell
docker build -t tasha:1.0.0 .
```

Run container with image
```shell
docker run --name tasha -p 8090:80 -it -d tasha:1.0.0
```
