<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Routing;

use Symfony\Component\Console\Command\Command as SfCommand;
use Symfony\Component\Console\Input\ {
    InputInterface,
    InputDefinition,
    InputOption
};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A Luthier Framework application console command
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Command
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var callable|string
     */
    protected $callback;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $help = '';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param string    $name     Command name
     * @param callable  $callback Command callback
     */
    public function __construct(string $name, callable $callback)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * Sets the parameter description
     * 
     * @param  string  $description Description
     * 
     * @return self
     */
    public function description(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Sets the parameter help
     * 
     * @param string $help
     * 
     * @return self
     */
    public function help(string $help)
    {
        $this->help = $help;
        return $this;
    }

    /**
     * Adds a new command parameter
     * 
     * @param string  $name        Parameter name
     * @param mixed   $shortcuts   Parameter shortcuts
     * @param string  $description Parameter description
     * @param string  $mode        Parameter mode (none|required|optional|array)
     * @param mixed   $default     Set the parameter default value
     * 
     * @throws \Exception
     * 
     * @return self
     */
    public function param(string $name, $shortcuts = null, string $description = '', string $mode = 'required', $default = null)
    {
        $modes = [
            'none' => InputOption::VALUE_NONE,
            'required' => InputOption::VALUE_REQUIRED,
            'optional' => InputOption::VALUE_OPTIONAL,
            'array' => InputOption::VALUE_IS_ARRAY
        ];

        if (! in_array($mode, $modes)) {
            throw new \Exception("Unknown command parameter '$mode' mode");
        }

        $this->params[] = new InputOption($name, $shortcuts, $mode, $description, $default);

        return $this;
    }

    /**
     * Gets the command name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the command callback
     * 
     * @return string|callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Gets the command description
     * 
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Gets the command help
     * 
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * Gets the command parameters
     * 
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Compiles the command to a Symfony Application command
     * 
     * @return \Symfony\Component\Console\Command\Command
     */
    public function compile()
    {
        $_command = &$this;

        return new Class($_command) extends SfCommand {

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
                if (! empty($this->_command->getParams())) {
                    $this->setDefinition(new InputDefinition($this->_command->getParams()));
                }
            }

            public function execute(InputInterface $input, OutputInterface $output)
            {
                $callback = \Closure::bind($this->_command->getCallback(), $this, SfCommand::class);
                call_user_func_array($callback, [
                    $input,
                    $output
                ]);
            }
        };
    }
}