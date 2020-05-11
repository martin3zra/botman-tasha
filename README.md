## Tasha Expenser

## Usages

To create the database structure and seed initial data please run the above command.
```shell
php artisan migrate --seed
```

Build docker image
```shell
docker build -t tasha:1.0.0 .
```

Run docker container with image
```shell
docker run --name tasha -p 8090:80 \
    -e DB_CONNECTION=sqlite \
    -e EXCHANGE_URL= \
    -e EXCHANGE_KEY= \
    tasha:1.0.0
```
