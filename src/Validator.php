<?php 

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier;

use Psr\Container\ContainerInterface;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints;

/**
 * Validator inspired by the CodeIgniter validation, using Symfony components
 * 
 * @author <anderson@ingenia.me>
 */
class Validator
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected $validator;
    
    /**
     * @var array
     */
    protected $rules = [];
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->validator = Validation::createValidator();
    }
    
    public function rule(string $name, string $fieldName = '', $constraints = [], $errorMessages = [])
    {
        $this->rules[] = [$name, $fieldName, $constraints, $errorMessages]; 
        return $this;
    }
    
    public function validate()
    {
        $validation = [];
        
        foreach($this->rules as [$name, $fieldName, $constraints, $errorMessages])
        {   
            $rules = [];
            
            if(is_string($constraints))
            {
                foreach(explode('|', $constraints) as $rule)
                {
                    $rules[] = $this->getRuleClass($rule);
                }
            }
            else if(is_array($constraints))
            {
                
            }
            else
            {
                continue; 
            } 
        }
        
        $this->rules = [];
    }
    
    
    private function getRuleClass($name)
    {
        if(count(explode(':', $name)) > 1)
        {
            [$name, $params] = explode(':', $name);
            $params = explode(',', $params);
        }
        else
        {
            $params = []; 
        }
        
        switch($name)
        {
            case 'required':
                return new Constraints\Required();
        }
    }
}