# Explorations into wrapping Symfony Applications into a Phar

## Building

```shell script
bin/console build # builds the phar in build/test.phar
```

## Running

```shell script
build/test.phar # should be an executable file, if this doesn't work try `php build/test.phar`
```
