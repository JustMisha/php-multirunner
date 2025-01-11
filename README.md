[![ru](docs/ru.svg)](docs/README.ru.md) ![PHP versions](docs/php_version.svg) [![license MIT](docs/license-MIT.svg)](LICENSE.md) ![coverage](docs/badge-coverage.svg)
# PHP MultiRunner

A package for running multiple processes in parallel in the background and, if necessary,
get the results of their work.

It is clear that, first of all, such parallel execution allows you to radically reduce 
the time of all these processes.

This package:
- has a very simple interface;
- can run any program, script or code that has an interpreter installed on the system;
- works on both Windows and Linux;
- allows to transfer large amounts of data between processes and your code - tests confirmed about 2Mb.

Under the hood, it uses proc_open() to start processes and hides numerous complexities 
and peculiarities of working with processes in PHP.

It does not require any other packages or extensions to be installed.

## Usage

### Run a single PHP script many times with different parameters and get its output messages.
```php
<?php

    use \JustMisha\MultiRunner\ScriptMultiRunner;

    $maxParallelProcesses = 512;    //  determined by the machine on which it is runs
    try {
        $runner = ScriptMultiRunner($maxParallelProcesses, "/full/path/to/script");
    } catch (RuntimeException $e) {
        // handle an exception
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
        // handle an exception
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

### General description of the interface

Six different classes can be used depending on the objects that are launched for execution:
1. **ProgramMultiRunner** to run several instances of one program;
2. **DiffProgramMultiRunner** to run miscellaneous programs;
3. **ScriptMultiRunner** to run several instances of one script;
4. **DiffScriptMultiRunner** to run different scripts (and/or even different interpreters);
5. **CodeMultiRunner** to run any code created in your program;
6. **DiffCodeMultiRunner** to run different code (possibly for different interpreters) created in your program.

The signature of the constructors of all these classes and their ``addProcess()`` method will naturally be different.

In any case, the first parameter of the constructor will always be the number of concurrent parallel processes 
that can be launched. This number depends entirely on the machine on which the package is running, 
that's why it is the first parameter and has no default value
> Explicit is better than implicit.
> --- [The Zen of Python](https://peps.python.org/pep-0020)

A good starting point would be 512 simultaneous parallel processes.

And then there are three possible use cases:
1. Run and get the results of all running processes &mdash; ```runAndWaitForResults()```;
2. Run and forget (do nothing) &mdash; ```runAndForget()```;
3. Run and get the first N results &mdash; ```runAndWaitForTheFirstNthResults()```.

#### Data Exchange Between Parent and Child Processes

The package uses the following data exchange scheme between parent code and child processes:
1. One-time data transfer in the form of parameters when a child process is added;
2. Receiving data from child processes from their standard output streams (stdout) and error output (stderr).

There is no data exchange between the parent process and running child processes after they are started.

To ensure that data other than plain text is properly transferred from a child process, it must be
properly encoded: serialization, json representation, base64 conversion or other means of representing the data.

### Limitations

It is not intended to use output redirection when starting processes because.
1. It may interfere with the established method of retrieving data from child processes;
2. It will not work on Windows because when the proc_open() function is called, the
   bypass_shell option is set to true and the process is started bypassing the cmd.exe shell.

### Dependency inversion during dependency injection

If you need to pass some ```MultiRunner``` child class as a parameter to a constructor or other method,
it is worth using the ```MultiRunnerInterface``` interface to remove the dependency on a particular class.
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

### More examples

[Run one program many times with different parameters and get its messages about the results of its work](docs/OneProgramRunAndWaitForResults.md)

[Run one program many times with different parameters and do nothing else (run and forget)](docs/OneProgramRunAndForget.md)

[Run a single PHP script many times with different parameters and get its output messages](docs/OneScriptRunAndWaitForResults.md)

[Run one PHP script many times with different parameters and get only a certain number of messages about the results (do not wait for all running processes to execute)](docs/OneScriptRunAndWaitForNthResults.md)

[Run one Python script many times with different parameters and get its output messages](docs/OnePythonScriptRunAndWaitForResults.md)

[Run one PHP script many times with different parameters and do nothing (run and forget).](docs/OneScriptRunAndForget.md)

[Run different scripts with different parameters and get his messages about the results of the work](docs/DiffScriptsRunAndWaitForResults.md)

[Run different scripts with different parameters and do nothing (run and forget)](docs/DiffScriptsRunAndForget.md)

[Run the PHP code many times with different parameters and get its output messages](docs/CodeRunAndWaitForResults.md)

[Run the PHP code many times with different parameters and do nothing (run and forget)](docs/CodeRunAndForget.md)

[Run PHP, python, and node.js code with different parameters and get its output messages](docs/DiffCodeRunAndWaitForResults.md)

[Run PHP, python, and node.js code with different parameters and do nothing (run and forget)](docs/DiffCodeRunAndForget.md)

## Installation
### You will need:

* [php](https://www.php.net/) (version >=7.4);
* [Composer](https://getcomposer.org/);
* [Git](https://git-scm.com) - for development.
* PHP instances that use this package, must allow ```proc_open()```. 
    This function  is often disabled in some environments due to security policies via
    ```disable_functions``` in ```php.ini```.


### For use in your project
```
composer require justmisha/php-multirunner
```
### For development

In the local project folder, execute
```
git clone https://github.com/JustMisha/php-multirunner.git your-folder-for-php-multirunner-code

composer install
```

## Testing

Run all tests, including linters and static analyzers, from the root of the project folder:
```
composer test
```
To run only unit tests:
```
composer phpunit
```
The package was developed in Windows, so to run the tests in Linux via Docker
you can use the ```tests\test-linux-all-php.cmd``` command file, which will run the
unit tests in Docker containers with php-cli 7.4, 8.0, 8.1, 8.2, 8.3. Or you can run a run
tests for each php version using a different file (e.g. ```test-linux-php-7.4.cmd```).

The tests use the python and node.js interpreters. If you don't have them installed,
then run the tests with the command line option ```--exclude python,node``` or use the configuration file 
```phpunit.exclude-python-node.xml``` configuration file.

## Contributing

Please send your suggestions, comments, and pull requests.

For large changes, please open a discussion to discuss your suggestions.

For pull requests, please make appropriate changes to the tests.

## Versioning

We use [SemVer](http://semver.org/) for versioning.

## Authors

* **Mikhail Trusov** - *php-multirunner* - [php-multirunner](https://github.com/JustMisha/php-multirunner)

See also the list of [contributors](https://github.com/your/project/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.