<?php

/**
 * Command class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Symfony\Component\Console\Command\Command as SfCommand;
use Symfony\Component\Console\Input\{InputInterface, InputArgument, InputDefinition, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

class Command
{
    protected $name;

    protected $callback;

    protected $description = '';

    protected $help = '';

    protected $params = [];

    public function __construct(string $name, callable $callback)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    public function description(string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function help(string $help)
    {
        $this->help = $help;
        return $this;
    }

    public function param(string $name, $shortcuts = null, string $description = '', string $mode = 'required', $default = null)
    {
        $modes = [
            'none'     => InputOption::VALUE_NONE,
            'required' => InputOption::VALUE_REQUIRED,
            'optional' => InputOption::VALUE_OPTIONAL,
            'array'    => InputOption::VALUE_IS_ARRAY,
        ];

        if(!in_array($mode, $modes))
        {
            throw new \Exception("Unknown command parameter '$mode' mode");
        }

        $this->params[] = new InputOption($name, $shortcuts, $mode, $description, $default);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getHelp()
    {
        return $this->help;
    }

    public function getParams()
    {
        return $this->params;
    }


    public function compile()
    {
        $_command = &$this;

        return new Class($_command) extends SfCommand
        {
            private $_command;

            public function __construct($_command)
            {
                $this->_command = $_command;
                parent::__construct($_command->getName());
            }

            public function configure()
            {
                $this->setName($this->_command->getName());
                $this->setDescription($this->_command->getDescription());
                $this->setHelp($this->_command->getHelp());
                if(!empty($this->_command->getParams()))
                {
                    $this->setDefinition(new InputDefinition($this->_command->getParams()));
                }
            }

            public function execute(InputInterface $input, OutputInterface $output)
            {
                $callback = \Closure::bind($this->_command->getCallback(), $this, SfCommand::class);
                call_user_func_array($callback, [$input, $output]);
            }
        };

    }
}