## Tasha Expenser

## Usages

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
