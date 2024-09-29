[![en](en.svg)](../README.md)
# PHP MultiRunner

Пакет для запуска множества процессов параллельно в фоне и, если необходимо, 
получения результатов их работы.

Понятно, что прежде всего, такое параллельное выполнение позволяет радикально сократить время 
выполнения всех этих процессов.

Этот пакет:
- имеет очень простой интерфейс;
- может запускать любую программу, скрипт или код, интерпретатор которых установлен в системе;
- работает как в Windows, так и в Линукс;
- позволяет передавать большие объемы данных между процессами и вашим кодом — тестами подтверждено около 2 Мб.

Под капотом для запуска процессов использует proc_open() и скрывает многочисленные сложности и особенности работы
с процессами в PHP.

Для своей работы не требует установки других пакетов и расширений.

## Использование

### Например, нужно запустить один скрипт PHP параллельно много раз с разными параметрами и получить его сообщения о результатах работы

```php
<?php

    use \JustMisha\MultiRunner\ScriptMultiRunner;

    $maxParallelProcesses = 512;    //  determined by the machine on which it is runs
    try {
        $runner = ScriptMultiRunner($maxParallelProcesses, "/full/path/to/script");
    } catch (RuntimeException $e) {
        // handle a runtime exception 
    }

    for($i = 1; $i <= 1000000; $i++) {
        $changingArg1 = $i - 1;
        $changingArg2 = $i + 1;
        $runner->addProcess((string)$i, $changingArg, $changingArg2);
    }
    
    $timeout = 15; // Timeout in seconds, depending on the machine it is running on.
    try {
        $results = $runner->runAndWaitForResults($timeout);
    } catch (RuntimeException $e) {
        // handle a runtime exception
    }
    
    foreach ($results as $processId => $processResult) {
        if $processResult->exitCode !== 0 {
            echo "There were errors in " . $processId . ": " . $processResult->stderr;
            continue;
        }
        $result = $processResult->stdout;
        // handle a success result, whatever it is
        
    };
```
### Общее описание интерфейса

В зависимости от объектов, которые запускаются на выполнение, можно использовать шесть разных классов:

| Объект, запускаемый на выполнение                                              | Используемый класс     |
|--------------------------------------------------------------------------------|------------------------|
| Программа                                                                      | ProgramMultiRunner     |
| Разные программы                                                               | DiffProgramMultiRunner |
| Скрипт                                                                         | ScriptMultiRunner      |
| Разные скрипты (и/или разные интерпретаторы)                                   | DiffScriptMultiRunner  |
| Код, создаваемый в вашей программе                                             | CodeMultiRunner        |
| Разный код (возможно под разные интерпретаторы), создаваемый в вашей программе | DiffCodeMultiRunner    |

Сигнатура конструкторов всех этих классов и их метода ```addProcess()``` будет, естественно, разной.

При этом первым параметром конструктора всегда будет число одновременных параллельных процессов, которые могут быть
запущены. Это число целиком и полностью зависит от машины, на которой работает пакет, поэтому оно является
первым параметром и не имеет значения по умолчанию  
> Явное лучше подразумеваемого (неявного).
> --- [The Zen of Python](https://peps.python.org/pep-0020)

Хорошей стартовой точкой для подбора будет 512 одновременных параллельных процессов.

И дальше возможны три варианта использования:
1. Запустить и получить результаты всех запущенных процессов &mdash; ```runAndWaitForResults()```;
2. Запустить и забыть (ничего не делать) &mdash; ```runAndForget()```;
3. Запустить и получить первые N результатов  &mdash; ```runAndWaitForTheFirstNthResults()```.

### Инверсия зависимости при внедрении зависимости

Если нужно передать какой-либо класс-наследник ```MultiRunner``` как параметр в конструктор или другой метод,
то стоит воспользоваться интерфейсом ```MultiRunnerInterface```, чтобы убрать зависимость от конкретного класса.
```php
    public function someMethodInSomeClass(MultiRunnerInterface $runner) {
        ...
        $processId = 0;
        foreach ($params as $param) {
            $processId++;
            $runner->addProcess((string)$processId, $param);
        }
        $timeToWait = 60;
        
        try {
            $results = $runner->runAndWaitForResults($timeToWait);
        } catch (RuntimeException $e) {
            ...
        }               
        ...
    }
```

### Другие примеры
[Запустить одну программу много раз с разными параметрами и получить ее сообщения о результатах работы](OneProgramRunAndWaitForResults.md)

[Запустить одну программу много раз с разными параметрами и больше ничего не делать (запустить и забыть)](OneProgramRunAndForget.md)

[Запустить один скрипт PHP много раз с разными параметрами и получить его сообщения о результатах работы](OneScriptRunAndWaitForResults.md)

[Запустить один скрипт PHP много раз с разными параметрами и получить только определенное количество сообщений о результатах работы (не дожидаться выполнения всех запущенных процессов)](OneScriptRunAndWaitForNthResults.md)

[Запустить один скрипт Python много раз с разными параметрами и получить его сообщения о результатах работы](OnePythonScriptRunAndWaitForResults.md)

[Запустить один скрипт PHP много раз с разными параметрами и ничего не делать (запустить и забыть)](OneScriptRunAndForget.md)

[Запустить несколько разных скриптов с разными параметрами и получить его сообщения о результатах работы](DiffScriptsRunAndWaitForResults.md)

[Запустить несколько разных скриптов с разными параметрами и ничего не делать (запустить и забыть)](DiffScriptsRunAndForget.md)

[Запустить код на PHP много раз с разными параметрами и получить его сообщения о результатах работы](CodeRunAndWaitForResults.md)

[Запустить код на PHP много раз с разными параметрами и ничего не делать (запустить и забыть)](CodeRunAndForget.md)

[Запустить код на PHP, python и node.js с разными параметрами и получить его сообщения о результатах работы](DiffCodeRunAndWaitForResults.md)

[Запустить код на PHP, python и node.js с разными параметрами и ничего не делать (запустить и забыть)](DiffCodeRunAndForget.md)

## Установка
### Для работы будет нужно:

  * [php](https://www.php.net/) (version  >=7.4);
  * [Composer](https://getcomposer.org/);
  * [Git](https://git-scm.com) — для разработки.


### Для использования в своем проекте
```
composer require justmisha/php-multirunner
```
### Для разработки

В локальной папке проекта выполнить
```
git clone https://github.com/JustMisha/php-multirunner.git your-folder-for-php-multirunner-code

composer install
```

## Тестирование

Запуск всех тестов, включая линтеры и статические анализаторы, из корня папки проекта:
```
composer test
```
Для запуска только юнит-тестов:
```
composer phpunit
```
Разработка пакета велась в ОС Windows, поэтому для запуска тестов в ОС Linux через Docker 
можно использовать командный файл ```tests\test-linux-all-php.cmd```, который прогонит 
юнит-тесты в докер-контейнерах с php-cli 7.4, 8.0, 8.1, 8.2, 8.3. Или можно запустить прогон
тестов для каждой версии php с помощью своего файла (например, ```test-linux-php-7.4.cmd```).

В тестах используется интерпретаторы python и node.js. Если они у вас не установлены,
то запускайте тесты с опцией командной строки ```--exclude python,node``` или воспользуйтесь
файлом конфигурацией ```phpunit.exclude-python-node.xml```.

## Участие

Пожалуйста, присылайте свои предложения, замечания и pull requests.

Для больших изменений, пожалуйста, откройте дискуссию, чтобы обсудить ваши предложения.

При запросах на слияние, пожалуйста, внесите соответствующие изменения в тесты.

## Версии

Для обозначения версий используется [SemVer](http://semver.org/).

## Автор

* **Михаил Трусов** - *php-multirunner* - [php-multirunner](https://github.com/JustMisha/php-multirunner)

См. также [участники](https://github.com/JustMisha/php-multirunner/contributors).

## License

Данный пакет лицензируется на условиях MIT Лицензии - смотри подробности [LICENSE.md](../LICENSE.md).

