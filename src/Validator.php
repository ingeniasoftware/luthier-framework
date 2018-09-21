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
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;

/**
 * Simple yet effective validator. It's based on Symfony Validator component
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
     * @var \Symfony\Component\Translation\Translator
     */
    protected $translator;

    /**
     * @var array
     */
    protected $rules = [];
        
    /**
     * @var array
     */
    protected $validationErrors = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $locale = $this->container->has('APP_LOCALE') ? $this->container->get('APP_LOCALE') : 'en';
        $validator = Validation::createValidatorBuilder();
        
        $translator = $this->container->has('translator')
            ? $this->container->get('translator')
            : new Translator($locale);
        
        $translator->addLoader('xlf', new XliffFileLoader());
        $translator->addResource('xlf', realpath( __DIR__ . '/../vendor/symfony/validator/Resources/translations/validators.' . $locale . '.xlf'), $locale);
        
        $validator->setTranslator($translator); 
        $validator->setTranslationDomain('validators');
        
        $this->validator = $validator->getValidator();
        $this->translator = $translator;
    }

    /**
     * Adds a new validation rule to the validator
     *
     * @param string        $field
     * @param string|array  $constraints
     * @param string[]      $messages
     *
     * @return \Luthier\Validator
     */
    public function rule(string $field, $constraints, array $messages = [])
    {
        if(!is_string($constraints) && !is_array($constraints)){
            throw new \InvalidArgumentException("Validation constraints must be declared as a string or an array");
        }
        
        $this->rules[] = [
            $field,
            $constraints,
            $messages
        ];
        return $this;
    }

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Runs the validator
     * 
     * Returns NULL if none constraints violations occurred, or an array
     * with the validation errors otherwise
     * 
     * @param array $data
     * @param array $constraints
     * 
     * @return NULL|array
     */
    public function validate($data = [], array $constraints = [])
    {
        if($data instanceof Http\Request){
            $data = $data->getRequest()->request->all();
        }
        
        $validationErrors = [];
        
        if(!empty($constraints)){
            foreach($constraints as $field => $validation){
                if(is_string($validation)){
                    $rules = $validation;
                    $messages = [];
                } else {
                    if (is_array($validation)) {
                        $rules = $validation[0];
                        $messages = $validation[1] ?? [];
                    } else {
                        throw new \InvalidArgumentException("Invalid validation constraints definition: must be a string or an array of constraints/error messages");
                    }
                }
                $this->rule($field,$rules,$messages); 
            }
        }

        foreach ($this->rules as [
            $field,
            $constraints,
            $messages
        ]) {
            $this->messages = $messages;
            $rules = [];
            $required = false;
            $matches = null;

            if (is_string($constraints)) {
                $constraints = explode('|', $constraints);
            }

            foreach ($constraints as $rule) {
                if ($rule === 'required') {
                    $required = true;
                    continue;
                }
                if (count(explode('matches:', $rule)) == 2 ){
                    [, $matches] = explode('matches:', $rule);
                    continue;
                }
                $rules[] = $this->parseRule($rule, $messages[$field] ?? null);
            }

            $violations = $this->validator->validate($data[$field] ?? null, $rules);
            
            if ($required && ! isset($data[$field])) {
                $translatedError = $this->translator->trans("This field is missing.");
                $violations->add(new ConstraintViolation(
                    $translatedError, 
                    $translatedError, 
                    [], 
                    null, 
                    null, 
                    null,
                    null,
                    null,
                    new Constraints\Required())
                );
            }
            
            if ($matches !== null && (!isset($data[$matches]) || $data[$matches] != $matches)) {
                $translatedError = $this->translator->trans("This value should be equal to {{ compared_value }}.");
                $violations->add(new ConstraintViolation(
                    $translatedError,
                    $translatedError,
                    ['{{ compared_value }}' => $data[$matches] ?? null],
                    null,
                    null, 
                    $matches,
                    null,
                    null,
                    new Constraints\EqualTo($matches))
                );
            }
            
            foreach ($violations as $violation) {
                $rule = explode('\\', get_class($violation->getConstraint()));
                $validationErrors[$field][] = $violation->getMessage();
            }
        }
         
        $this->validationErrors = $validationErrors;
        $this->rules = null;

        return empty($validationErrors);
    }
    
    /**
     * Same as validate(), but throws an exception if a constraint violatio
     * occurs
     * 
     * @param array $data
     * 
     * @throws \Exception
     * 
     * @return void
     */
    public function validateOrFail($data = [], array $constraints = [])
    {
        if($this->validate($data, $constraints) !== true){
            throw new \Exception("Validation error: one or more constraint violations occurred with the submited data");
        }
    }
    
    /**
     * Gets the last validation error list
     *  
     * @param  string $field Specific field name
     * 
     * @return array
     */
    public function getValidationErrors(string $field = null)
    {
        return $field !== null 
            ? $this->validationErrors[$field] ?? []
            : $this->validationErrors;    
    }

    /**
     * Compiles the rule string to a Symfony validator's constraint 
     * 
     * @param string $name
     * @param string $message
     * 
     * @return \Symfony\Component\Validator\Constraint
     */
    private function parseRule($name, $message)
    {
        $operators = ['>=', '<=', '>', '<', '===', '==', '!==', '!='];
        
        if (count(explode(':', $name)) > 1) {
            
            [
                $name,
                $params
            ] = explode(':', $name);
            $params = explode(',', $params);  
            
        } else {
            
            $isOperator = false;
            
            foreach($operators as $operator){
                
                if(count(explode($operator, $name)) > 1){
                    $params = explode($operator, $name);
                    $params = [ $params[1] ];
                    $name = $operator;
                    $isOperator = true;
                    break;
                }
                
            }
            
            if(!$isOperator){
                $params = [];
            }
        }
        
        $constraint = null;

        //
        // Symfony Validator's constraints instances
        // 
        switch ($name) {
            /*
             * Regex validation
             */
            case 'regex':
                $constraint = new Constraints\Regex([
                    'pattern' => $params[0]
                ]);
                break;
            /*
             * String lenght validation (min) 
             */
            case 'min_length':
                $constraint = new Constraints\Length([
                    'min' => $params[0]
                ]);
                break;
            /*
             * String lenght validation (max)
             */
            case 'max_length':
                $constraint = new Constraints\Length([
                    'max' => $params[0]
                ]);
                break;
            /*
             * String collection ('enum' like) validation 
             */
            case 'in_list':
                $constraint = new Constraints\Choice($params);
                break;
            /*
             * Email validation 
             */
            case 'email':  
                $constraint = new Constraints\Email($params[0]);
                break;
            /*
             * Numeric integer validation
             */
            case 'integer':
                $constraint = new Constraints\Regex([
                    'pattern' => '/^[\-+]?[0-9]+$/'
                ]);
                break;
            /*
             * Numeric decimal validation 
             */
            case 'decimal':
                $constraint = new Constraints\Regex([
                    'pattern' => '/^[\-+]?[0-9]*[\.,][0-9]+$/'
                ]);
                break;
            /*
             * Numeric validation (both integer and decimal)
             */
            case 'number':
                $constraint = new Constraints\Regex([
                    'pattern' => '/^[\-+]?([0-9]+|[0-9]*[\.,][0-9]+)$/'
                ]);
                break;
            /*
             *  Numeric range validation
             */
            case 'in_range':   
                $constraint = new Constraints\Range([
                    'min' => $params[0],
                    'max' => $params[1],
                ]);
                break;
            /*
             *  Numeric Greater Than or Equal (>=) validation
             */
            case 'greater_than_equal_to':
            case 'gte':
            case '>=': 
                $constraint = new Constraints\GreaterThanOrEqual($params[0]);
                break;
            /*
             *  Numeric Greater Than (>) validation
             */
            case 'greater_than':
            case 'gt':
            case '>':
                $constraint = new Constraints\GreaterThan($params[0]);
                break;
            /*
             *  Numeric Lesser Than or Equal (<=) validation
             */
            case 'less_than_equal_to':
            case 'lte':
            case '<=':
                $constraint = new Constraints\LessThanOrEqual($params[0]);
                break;
            /*
             *  Numeric Lesser Than (<) validation
             */
            case 'less_than':
            case 'lt':
            case '<':
                $constraint = new Constraints\LessThan($params[0]);
                break;
            /*
             * String identical (===) validation 
             */
            case 'identical':
            case '===': 
                $constraint = new Constraints\IdenticalTo($params[0]);
                break;
            /*
             * String equal (==) validation
             */
            case 'equal':
            case '==':  
                $constraint = new Constraints\EqualTo($params[0]);
                break;
            /*
             * String not identical (!==) validation 
             */
            case 'not_identical':
            case '!==': 
                $constraint = new Constraints\NotIdenticalTo($params[0]);
                break;
            /*
             * String not equal (!=) validation
             */
            case 'not_equal':
            case '!=':
                $constraint = new Constraints\NotEqualTo($params[0]);
                break; 
            default:
                throw new \Exception('Unknown validation rule "' . $name . '"');
        }
        
        //
        // Constraints translated error messages
        //
        switch ($name) {
            case 'range':
                $translatedMessage = $this->translator->trans(
                    !empty($message) ? $message : $constraint->minMessage
                );
                $constraint->minMessage = $translatedMessage;
                $constraint->maxMessage = $translatedMessage;
                break;
            case 'min_length':
                $constraint->minMessage = $this->translator->trans(
                    !empty($message) ? $message : $constraint->minMessage
                );
                break;
            case 'max_length':
                $constraint->maxMessage = $this->translator->trans(
                    !empty($message) ? $message : $constraint->maxMessage
                );
                break;
            default:
                $constraint->message = $this->translator->trans(
                    !empty($message) ? $message : $constraint->message
                );
        }
       
        return $constraint;
    }
}