<!-- omit in toc -->
# Process Management
<!-- TOC start -->

**Index of contents**:

- [Underlying logic](#underlying-logic)
  - [What is the problem?](#what-is-the-problem)
  - [What are processes?](#what-are-processes)
  - [Structure definition](#structure-definition)
- [`AbstractProcess` class: core of the management](#abstractprocess-class-core-of-the-management)
  - [1. `preConditionInput`](#1-preconditioninput)
  - [2. `mainCode`](#2-maincode)
  - [3. `postConditionProcess`](#3-postconditionprocess)
- [`AbstractProcess` class: dependencies](#abstractprocess-class-dependencies)
- [Implement subclasses of `AbstractProcess`](#implement-subclasses-of-abstractprocess)
  - [Process class autoloading](#process-class-autoloading)
  - [Why the need to create this special autoloader?](#why-the-need-to-create-this-special-autoloader)
  - [Implementing the process classes](#implement-process-classes)
  

<!-- TOC end -->
</br>

---
<!-- TOC --><a name="underlying-logic"></a>
## Underlying logic

<!-- TOC --><a name="what-is-the-problem"></a>
### What is the problem
The problem is that when a message is sent to the bot, it always executes the same file and is not able to understand on its own the status of the procedure that the user is carrying out on the bot. Therefore this state must be managed manually.

<!-- TOC --><a name="what-are-processes"></a>
### What are processes
Processes indicate the state the bot is in at a given time. They therefore indicate the perspective from which the code must see the input messages sent by the user and allow them to be managed differently based on the context.

<!-- TOC --><a name="structure-definition"></a>
### Structure definition
The processes, practically, are nothing more than strings that identify the state of the bot.
Specifically, they are of the form `FirstProcess\InternalProcess\FinalProcess` (as if they were folder paths in an operating system or a route in a website).
Furthermore, each of these processes may need to save data to pass to the next process, so they also have some `data` to save.

The structure in which to save these processes is a database table (\*), composed as follows:

| user_idtelegram \[PK] | process_name | process_data |
| --- | --- | --- |
| 67756473 | FirstProcess\\InternalProcess | _NULL_ |

- `user_idtelegram` allows you to uniquely connect the process with the Telegram account of the user who is using the bot. In fact, each process is different for each Telegram user. This 1:1 relationship actually also allows you to insert the other two fields into a possible table in which user data is managed (for example a possible `Users` table that saves all users and other info)
- `process_name` is the name of the user's active process, therefore its context in the bot (in the form described a few lines above)
- `process_data` contains any data to be "carried" when switching from one process to another

The definition of the processes, which will then turn into the definitions of the classes that will manage them and the namespaces that will identify these classes, is a design operation that should be done in the most rigorous way before starting to work on the code.

<sub><sup>(*) You could also use a similar structure, such as a json file or something else. However, a table in a database seems like the most convenient way to handle it to me. Obviously, however, it always depends on the project specifications.</sup></sub>

---
<!-- TOC --><a name="abstractprocess-class-core-of-the-management"></a>
## `AbstractProcess` class: the core of the management
The `AbstractProcess` class has only one publicly usable function, namely `codeToRun()` which is divided internally into 3 parts:
1. `preConditionInput()`
2. `mainCode()`
3. `postConditionProcess()`

<!-- TOC --><a name="1-preconditioninput"></a>
### 1. `preConditionInput`
Into this function the (user) input is validated, i.e. the command that the user sent to the bot and which must be managed by the process.
The command can be of two types:
- static
- dynamic

Static ones, by their very nature, must be written directly into the process-specific class definition, which extends `AbstractProcess`:
```php
...

$valid_static_inputs = [
  "Command value" => "functionToHandleTheCommand",
  "/start" => "startCommand",
  "Send Post" => "sendPostCommand",
  ...
];

...
```

These are controlled by the `validateStaticInputs()` method, which returns `true` if a match is found between the keys of the `$valid_static_inputs` array and the message sent by the user to the bot, otherwise `false`.

The dynamic ones, however, can be various and may not even be present at times.
They are checked after the static ones by the `validateDynamicInputs()` function which returns `true` by default, but can be overridden in process-specific subclasses.

If at least one valid match emerges from these two checks, the pre-conditions of the process are exceeded and the `$function_to_call` attribute must be filled with the value associated with the command which the match occurred with (e.g. if the command is `/start` then the function to call will be `"startCommand"`).
This assignment is done automatically for static inputs, but must be done manually for dynamic ones.

<!-- TOC --><a name="2-maincode"></a>
### 2. `mainCode`
This function executes the `$function_to_call` associated with the command found in the pre-conditions phase.
Essentially in the subclasses of `AbstractProcess` you will have to worry about defining these functions (associated with the commands). Therefore, is here that the logic of the commands resides, which are the core of the processes and of this entire infrastructure. **The work of those who implement the commands lies not only in setting up the entire surrounding environment, but also in creating these functions in the appropriate classes of processes.**

<!-- TOC --><a name="3-postconditionprocess"></a>
### 3. `postConditionProcess`
In this last function/phase the process in the database will have to be changed to prepare the system to the management of the subsequent process.
The process obviously changes based on the command and what is expected to happen in the next steps of the procedure.
For example, it may happen that a procedure ends and therefore you want to return to the initial menu; or it may happen that you need to go to the next step of a procedure and then add a process to the current process path.

This is the only part of the main process management skeleton in which the `AbstractProcess` class deals indirectly (via the `$_User` class) with the database, changing the process.

Therefore, you need to be careful to call the `setNextProcess($process_name, $process_data=null)` function at the end of each function implementation that handles a command.
If you don't call it, the next process will be set to `null` in the database (which is equivalent to "no processes started", so ideally the initial menu).

If you want to proceed with the processes, you will need to write like this:
```php
// (...) implementation of the function

$this->setNextProcess($this->appendNextProcess("NextProcessName"));
```

**It is good to remember that the process names must coincide with existing classes in the database**.
The `appendNextProcess(string)` method takes the name of the current running process and appends the string to it, preceded by the process separator "\\".

However, if you want to go back one process, then you will need to do:
```php
// (...) implementation of the function

$this->setNextProcess($this->getPreviousProcess());
```

However, this method will take the current process and remove the last part of the process path, resulting in the name of the process preceding the current process.

---
<!-- TOC --><a name="abstractprocess-class-dependencies"></a>
## `AbstractProcess` class: dependencies
The constructor of the `AbstractProcess` class asks for two parameters:
- `_Bot`, i.e. the object through which you can interact with the methods of the Telegram bot API
- `_User`, i.e. the object that allows you to manage all information strictly related to the user
Among the information related to the user, in the `User` class, there is also the management of processes, through the `_ProcessHandler` class attribute, which indicates the object capable of interfacing with the database to delete, modify and create records for jobs.

<!-- TOC --><a name="implement-subclasses-of-abstractprocess"></a>
## Implement subclasses of `AbstractProcess`
There are several considerations to make regarding the implementation of the subclasses of `AbstractProcess`.

<!-- TOC --><a name="process-class-autoloading"></a>
### Process class autoloading
First of all, they must all be implemented (including `AbstractProcess`) in a single folder (I recommend calling it `processes`).
Just outside this folder, a specific autoload function must be implemented to be able to load the process class files when they are encountered during program execution (like `process_autoloader.php` file inside this project). The autoload is as follows:
```php
<?php

spl_autoload_register(function($class) {
  $exploded_classname = explode("\\", $class);
  $count_subprocesses = count($exploded_classname);
  $relative_classname = $exploded_classname[$count_subprocesses - 1];

  $directory_where_search = __DIR__ . DIRECTORY_SEPARATOR . "processes" . DIRECTORY_SEPARATOR;

  $_RDI = new RecursiveDirectoryIterator($directory_where_search);
  $_RII = new RecursiveIteratorIterator($_RDI);
 
  foreach ($_RII as $file) {
      if ($file->isDir()){
          continuous;
      }

      $file_path = $file->getPathname();
      $file_name = $file->getFilename();
      if (is_file($file_path) && $file_name == $relative_classname.".php") {
          require_once $file_path;
      }
  }
});

?>
```

Essentially, the name of the class is taken (parameter `$class`) (which corresponds to the name of the process path [[###Implementing the process classes|as I will explain in the next paragraph]], the relative name of the class is extrapolated (`$relative_classname`), i.e. its name without the other parts of the namespace.
After that you need to indicate the folder from which to start searching for process classes (`$directory_where_search`) and the rest of the code will take care of digging recursively in each subfolder until it finds the correspondence with the class file (`{$relative_class }.php`).
If it finds it, it executes the `require_once` of the file path to include the process class.
This way if there is a process class repeated several times in different folders, the autoloader will include all classes with the same name, but from different folders.

!!! \[The best thing would be to ensure that only the relevant class is included and not all those with the same name]

<!-- TOC --><a name="why-the-need-to-create-this-special-autoloader"></a>
### Why the need to create this special autoloader
This autoloader is different from the autoloader recommended by PSR-4 because, regarding the individual process classes, the folder structure is not binding.
This means that if I had a class called `FirstProcess\InnerProcess\FinalProcess`, to be consistent with the PSR-4 autoloader I should create a `FirstProcess` folder, with a `InternalProcess` folder inside, with the `FinalProcess` class inside. Obviously this structure, however tidy, risks becoming a chaotic tangle of folders and paths that are difficult to manage.

This modified autoloader does not prevent you from recreating a similar structure, it simply allows you to handle process classes differently, unlike PSR-4.
In fact, the creation of folders even becomes fundamental in the case in which two processes belonging to two different paths have the same name. In this case I could not create two files with the same name to represent two classes, which are in fact different. Then there it is necessary to put them in two different folders, identified by the parent process that best differentiates them.

For example, if I had designed two different processes for my bot like `CreatePost\SendPost` and `MergePost\SendPost`, I could not insert two `SendPost.php` files in a hypothetical `process/` folder with all the process classes inside. Therefore it's better to create two subfolders, respectively `create_post_process/` and `merge_post_process/`, with the two `SendPost.php` files specific for the two processes inside.
During execution, for the dynamic distinction of the two classes it is still essential to compare the namespace.

<!-- TOC --><a name="implement-process-classes"></a>
### Implementing the process classes
The classes must be created in the processes folder (`processes`, as suggested). Subfolders can also be created to have the possibility of defining processes (therefore classes, therefore files) with the same name but belonging to different paths (as I explained better in the previous paragraph).

As I mentioned previously, the first thing to do in the file of each specific class of a process is to insert the correct namespace, which corresponds to the series of processes to which the process you want to implement belongs.
For example:
```php
namespace FirstProcess\InternalProcess;

class FinalProcess extends AbstractProcess {
  // ...
}
```
The `FirstProcess`, following the same logic, will have no namespace.

Once this is done, the things to set are:
- the `$valid_static_inputs` attribute to insert all possible valid static inputs for the process you want to implement;
- (optional) override the `validDynamicInputs()` method if there are dynamic inputs to check in the process;
- all the functions associated with the inputs (static and dynamic) present in the class (and therefore specific to the process);

Final example:
```php
namespace FirstProcess;

class InternalProcess extends AbstractProcess {
  protected $valid_static_inputs = [
  "/send_name" => "sendName",
  "<- back" => "back"
  ];

  protected function sendName() {
  // ...

  $this->setNextProcess($this->appendNextProcess("FinalProcess"));
  }

  protected function back() {
  // ...

  $this->setNextProcess($this->getPreviousProcess());
  }
}
```
