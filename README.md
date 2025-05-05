<!-- omit in toc -->
# 📂 telegram-bot-basic-project-structure

<!-- omit in toc -->
## ❓ What is this project
This is a set of folders and PHP files to start a generic Telegram Bot project. In the following README will be explained the technical structure and the logic behind the communication between classes.

<!-- omit in toc -->
## 📖 Documentation
The code contains a lot of useful comments, so the information about every specific class is inside the code. Here I will only describe some parts of the project.

This is a UML diagram that explains graphically the structure of the classes:

![uml_diagram_of_project](docs/bot-project-uml.png)


**Index of paragraphs**:
<!-- TOC start -->

- [Folder structure](#folder-structure)
- [Code style](#code-style)
- [Installed libraries](#installed-libraries)
  - [Class `TelegramBotApiCustom`](#class-telegrambotapicustom)
- [List of all example classes or editable classes](#list-of-all-example-classes-or-editable-classes)
- [Autoloaders](#autoloaders)
- [Class `ConfigurationInfo`](#class-configurationinfo)
- [The `hook.php` file](#the-hookphp-file)
- [Class `BaseEntity`](#class-baseentity)
- [Classes of `view/` folder](#classes-of-view-folder)
- [Authorization rules](#authorization-rules)
- [About processes](#about-processes)

<!-- TOC end -->
</br>

<!-- TOC --><a name="folder-structure"></a>
### Folder structure
For the logic of the architecture and the structure of the project I was inspired by MVC. The following are the main folders:

- `config/` contains configuration files and classes to handle it </br>
- `control/` contains classes that handles the processes of the bot </br>
- `docs/` contains some useful documents about the project </br>
- `entities/` contains classes that communicate with external services (like APIs or a database), or classes that implements some specific objects </br>
- `exceptions/` contains custom exceptions </br>
- `logs/` folder to store logs (need to be set by the server as a default class) </br>
- `vendor/` contains [Composer](https://getcomposer.org/)'s downloaded libraries </br>
- `view/` contains classes to handle the messages shown to the final user on the bot and everything about the "UI" of the bot (messages, emoticons, buttons, etc...) </br>

You can see the folders `entities/`, `view/` and `control/processes/` as the MVC folders. </br>

<!-- TOC --><a name="code-style"></a>
### Code style
The coding style followed in the classes of this project (except for the external library classes) is the following:

| Type of variable            | Style               |
| --------------------------- | ------------------- |
| **Class instance variable** | \$_InstanceName     |
| **Class definition**        | ClassName           |
| **Attribute or variable**   | snake_case_variable |
| **Method of function**      | camelCaseFunction   |
| **Constants**               | CONSTANT_NAME       |

<!-- TOC --><a name="installed-libraries"></a>
### Installed libraries
In this example base project I installed two libraries:
- [`telegram-bot-sdk`](https://github.com/irazasyed/telegram-bot-sdk): an unofficial Telegram Bot API SDK
- [`meekrodb`](https://github.com/SergeyTsalkov/meekrodb): a simple library to handle database MySQL calls

You can change these libraries with others with the same purpose (interface with Telegam Bot API methods and interface with database), but I recommend anyway to use some library to handle this two fundamental aspects of the structure.

<!-- TOC --><a name="class-telegrambotapicustom"></a>
#### Class `TelegramBotApiCustom`
Into `entities/tgbotapi_custom_interface/` folder I created this class that extends the `Telegram\Bot\Api` class of the [`telegram-bot-sdk`](https://github.com/irazasyed/telegram-bot-sdk) library. Obviously it's a fairly personal modification based on my needs. </br>

<!-- TOC --><a name="list-of-all-example-classes-or-editable-classes"></a>
### List of all example classes or editable classes
You can delete:
- `control/processes/Main.php`: I recommend you to modify this class and don't delete it. Every bot need a Main process (ideally, the first main menu)
- `control/processes/firstpath/SameNameClass.php` and `control/processes/secondpath/SameNameClass.php`
- `entities/authorization_rules/CheckIfIsActiveRule.php` and `entities/authorization_rules/CheckIfIsTesterRule.php`

You can modify:
- `entities/tgbotapi_custom_interface/`: you can modify and personalize all classes
- `entities/ProcessHandler.php`: you should modify the methods based on your database structure
- `entities/User.php`: you should modify the constructor and the attributes based on your database structure
- `hook.php`
  
Remember that when you modify `User` and `TelegramBotApiCustom` classes you need to check the `AbstractProcess` class, that use them as aggregate classes.

In general, when you want to make some modification take a look to the UML diagram. </br>

<!-- TOC --><a name="autoloaders"></a>
### Autoloaders
There are three autoloaders in this project, that you must call at the beginning of the webhook file (`hook.php`, in this project):
- `vendor/autoload.php` is the one that loads the Composer's libraries
- `/project_autoloader.php` is the one that loads all the classes of the project. The project must meet the [PSR-4 standard](https://www.php-fig.org/psr/psr-4/) (in the definition of the namespaces, for example) to make the autoloader work. The `Psr4AutoloaderClass` is the class to handle the definition of the standard autoloader
- `control/processes_autoloader.php` is the one to upload the classes of processes, handled differently from the others. [In this file](control/PROCESSES_README.md) I explain why it has to be different.

With these three autoloaders every class (created following the rules) will be automatically uploaded during the run-time. </br>

<!-- TOC --><a name="class-configurationinfo"></a>
### Class `ConfigurationInfo`
The `ConfigurationInfo` class is a [singleton class](https://en.wikipedia.org/wiki/Singleton_pattern) to handle the informations into the file `config.json`. </br>
You can change the `config.json` file as you wish and write the class methods accordingly.

In my personal `config.json` file there are two identical set of attributes, but one is for testing and develop time and the other one is for production time:
```json
{
  "testing": {
    "TELEGRAM_BOT_API_TOKEN":"tokentousewhiledeveloping"
    // (...)
  },
  "production": {
    "TELEGRAM_BOT_API_TOKEN":"tokentouseinproduction"
    // (...)
  }
}
```

With this configuration you can set two different environments that changes when you change a simple parameter into the `ConfigurationInfo` class call:
```php
// Static method of singleton ConfigurationInfo class
public static function setInstance(bool $testing=false) {...}
```
```php
// ConfigurationInfo instance in hook.php

// if you want to act in testing environment -->
$_SystemConfig = ConfigurationInfo::setInstance(true);
// if you want to act in production environment -->
$_SystemConfig = ConfigurationInfo::setInstance();
```
</br>

<!-- TOC --><a name="the-hookphp-file"></a>
### The `hook.php` file
This file is an example of a standard file to be used as "access point" of the Telegram Bot requests. The URL of this file should be set as webhook of the Telegram Bot (with the API call [`setWebhook`](https://core.telegram.org/bots/api#setwebhook)). </br>

<!-- TOC --><a name="class-baseentity"></a>
### Class `BaseEntity`
This class, by its `__call` magic method, provides the getters and setters of every attribute into the subclasses. Every new "entity" class should extend this class.
<br>

<!-- TOC --><a name="classes-of-view-folder"></a>
### Classes of `view/` folder
The `MenuOptions` class is a class of constants only, which represents the bot's static commands. The constants should be the keys of the array `$valid_static_inputs` in each process class (depending on the class):
```php
// (into MenuOption class)
  public const COMMAND_START = '/start';
  public const COMMAND_RESTART = '/restartbot';
```
```php
// (into a process class)
  protected array $valid_static_inputs = [
    view\MenuOptions::COMMAND_START => "startProcedure",
    view\MenuOptions::COMMAND_RESTART => "restartProcedure"
  ];
```

The `ViewWrapper` class is similar to `BaseEntity` class. This class has a defined `__callStatic` magic method which, for every "get" static call of the sub-classes (`Keyboards` and `InlineKeyboards`), returns the formatted buttons to set as argument into `reply_markup` parameter, into a `sendMessage` request, for example. </br>

The buttons can be defined statically as public constant into the subclasses (is described into the specific file of the classes how to do this). </br>
For `Keyboards` and `InlineKeyboards` are used, respectively, `KeyboardsTrait` and `InlineKeyboardsTrait`, two [traits](https://www.php.net/manual/en/language.oop5.traits.php#:~:text=Traits%20are%20a%20mechanism%20for,living%20in%20different%20class%20hierarchies.) which offer the function to create the keyboard and the inline keyboard from an array:
```php
// (into Keyboards class)
  public const MAIN_MENU = [
    [MenuOptions::COMMAND_START, MenuOptions::COMMAND_RESTART]
  ];
```
```php
// (example of usage)
$_Bot->sendMessage([
  'text' => "==> " . $test,
  'reply_markup' => Keyboards::getMainMenu()
]);
```
</br>

<!-- TOC --><a name="authorization-rules"></a>
### Authorization rules
When a Telegram user sends messages to the bot it might be useful to check some properties, for example if he is able to access or his legal permit has expired. These are the **rules**. Into the `entities/authorization_rules/` folder there are the files which handle the rules. </br>

For each of these properties you can create a new class that extends the `Rule` base class (as, in the example, `CheckIfIsActiveRule` and `CheckIfIsTesterRule`) and define the `rule()` method, as in the example below:
```php
// (for example) The bot can be used only from users activated from the admin
class CheckIfIsActiveRule extends Rule {

  public function rule() {
    if ($this->getUser()->isActive()) {
      return true;
    }
    return false;
  }

}
```

Then you have to add the instances of the classes you've created into the `private function rulesToAdd()`, into the `UserAuthorization` class:
```php
private function rulesToAdd() {
  $this->addRule(new CheckIfIsActiveRule($this->getUser(), $this->getSystemConfig()));
  $this->addRule(new CheckIfIsTesterRule($this->getUser(), $this->getSystemConfig()));
}
```

The `UserAuthorization` class method `verifyAuthorization()` will execute the `rule()` method of every instance you have added into `rulesToAdd()` method. So you can verify all the necessary rules and also get specific error messages for each one, in case some of the rules doesn't pass the check. </br>

<!-- TOC --><a name="about-processes"></a>
### About processes
An exhaustive description of processes is into [control/processes.md](control/PROCESSES_README.md) file. </br>
