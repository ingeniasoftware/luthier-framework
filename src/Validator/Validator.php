<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Validator;

use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Luthier\Http\Request;

/**
 * Luthier Framework validator
 *
 * @author <anderson@ingenia.me>
 */
class Validator
{
    const OPERATORS = ['>=', '<=', '>', '<', '===', '==', '!==', '!='];
    
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
        $locale = $this->container->has('APP_LANG') ? $this->container->get('APP_LANG') : 'en';
        
        $translator = $this->container->has('translator')
            ? $this->container->get('translator')
            : new Translator($locale);
        
        $translator->addLoader('xlf', new XliffFileLoader());
        $translator->addResource('xlf', realpath( __DIR__ . '/../../vendor/symfony/validator/Resources/translations/validators.' . $locale . '.xlf'), $locale, "validators");
        $this->translator = $translator;

        $this->reset(true);
    }

    /**
     * Resets the validator
     *
     * @param bool $preserveErrors Preserve the validation errors?
     * 
     * @return void
     */
    public function reset(bool $preserveErrors = false)
    {
        $validator = Validation::createValidatorBuilder();
        $validator->setTranslator($this->translator);
        $validator->setTranslationDomain('validators');
        
        $this->validator = $validator->getValidator();  
        $this->rules = [];
        
        if(!$preserveErrors) {
            $this->validationErrors = [];
        }
    }
    
    /**
     * Adds a new validation rule to the validator
     *
     * @param string        $field
     * @param string|array  $constraints
     * @param string[]      $messages
     *
     * @return self
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
     * Returns TRUE if no constraints violations occurred, FALSE instead
     * 
     * @param array $data        Data to be validated      
     * @param array $constraints Constraints
     * @param bool  $bailAll     Stop on first constraint error on each field?
     * 
     * @return bool
     */
    public function validate($data = [], array $constraints = [], bool $bailAll = false)
    {
        if(!is_array($data) && (!($data instanceof Request))){
            throw new \InvalidArgumentException('Validation data must be an array or an instance of ' . Request::class);
        }
        
        if($data instanceof Request){
            $data = $data->getRequest()->request->all();
        }
        
        $validationErrors = [];
        
        if (!empty($constraints)) {
            foreach ($constraints as $field => $validation) {
                if (is_array($validation)) {
                    $rules = $validation[0];
                    $messages = $validation[1] ?? [];
                } else {
                    $rules = $validation;
                    $messages = [];
                }
                $this->rule($field,$rules,$messages);
            }
        }

        foreach ($this->rules as [
            $field,
            $rules,
            $messages
        ]) {
            $this->messages = $messages;
            $required = false;
            $matches = null;
            $nullable = false;
            $sfRules = [];
            $bail = $bailAll;
            $error = false;

            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }

            foreach ($rules as $name => $params) {
                if(is_string($params) && is_int($name)){
                    // String based constraint definition:
                    $name = $params;  
                    if (count(explode(':', $name)) > 1) {
                        [
                            $name,
                            $params
                        ] = explode(':', $name);
                        $params = explode(',', $params);   
                    } else {
                        
                        $isOperator = false;
                        
                        foreach(self::OPERATORS as $operator){                    
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
                } else {   
                    // Array based constraint definition:
                    if (is_scalar($params) || is_array($params)) {
                        if (is_scalar($params)) {
                            $params = [$params];
                        } 
                    } else {
                        $params = []; 
                    }
                }
                
                // Special 'required' pseudo-constraint
                if ($name === 'required') {
                    $required = true;
                    continue;
                }
                                
                // Special 'matches' pseudo-constraint
                if ($name === 'matches' && isset($params[0]) && is_string($params[0])){
                    $matches = $params[0]; 
                    continue;
                }
                
                // Is a nullable field?
                if ($name === 'nullable') {
                    $nullable = true;
                    continue;
                }
                
                // The validation must stop at first error?
                if ($name === 'bail') {
                    $bail = true;
                    continue;
                }
                                     
                // Get the actual Symfony Constraint object
                $sfRules[] = $this->getSfConstraint($name, $params, $messages[$name] ?? null);
            }
            
            if ($required && !$nullable) {
                $sfRules[] = $this->getSfConstraint('not_blank', [], $messages[$name] ?? null);
                $sfRules[] = $this->getSfConstraint('not_null', [], $messages[$name] ?? null);
            }
                        
            $violations = new ConstraintViolationList(); 
            
            if ($required && ! isset($data[$field])) {
                $error = true;
                $errorMessage = $messages['required'] ?? "This field is missing.";
                $translatedError = $this->translator->trans($errorMessage, [], "validators");
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
                      
            if ($matches !== null && (!isset($data[$matches]) || !isset($data[$field]) || $data[$matches] != $data[$field]) && (!$bail || ($bail && !$error))) {
                $error = true;
                $errorMessage = $messages['matches'] ?? "This value should be equal to {{ compared_value }}.";
                $violations->add(new ConstraintViolation(
                    $this->translator->trans($errorMessage, ["{{ compared_value }}" => '[' . $matches . ']'], "validators"),
                    $this->translator->trans($errorMessage, [], "validators"),
                    [
                        "{{ value }}" => $data[$field] ?? null, 
                        "{{ compared_value }}" => $data[$matches] ?? null, 
                        "{{ compared_value_type }}" => "string"
                    ],
                    $data[$field] ?? null,
                    null, 
                    $data[$field] ?? null,
                    null,
                    null,
                    new Constraints\EqualTo($matches))
                );
            }
            
            if(!$bail || ($bail && !$error))
            {
                foreach ($sfRules as $sfRule) {
                    $violation = $this->validator->validate($data[$field] ?? null, $sfRule);
                    $violations->addAll($violation);
                    if (count($violation) > 0 && $bail) {
                        break;
                    }
                }
            }

            foreach ($violations as $violation) {
                $validationErrors[$field][] = $violation->getMessage();
            }
        }
         
        $this->validationErrors = $validationErrors;

        return empty($validationErrors);
    }
    
    /**
     * Same as validate(), but throws an exception if a constraint violation
     * occurs 
     * 
     * @param array $data        Data to be validated      
     * @param array $constraints Constraints
     * @param bool  $bailAll     Stop on first constraint error on each field?
     * 
     * @throws \Exception
     * 
     * @return void
     */
    public function validateOrFail($data = [], array $constraints = [], bool $bailAll = false)
    {
        if($this->validate($data, $constraints, $bailAll) !== true){
            throw new Exception\ValidationConstraintException("One or more constraint violations occurred with the submited data");
        }
    }
    
    /**
     * Gets the validation error list
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
     * Sets the validation errors list
     * 
     * @param array $errors
     */
    public function setValidationErrors(array $errors)
    {
        $this->validationErrors = $errors;
    }

    /**
     * Compiles the given rule and parameters to a Symfony validator's constraint 
     * 
     * @param string $name
     * @param array  $params
     * @param string $message
     * 
     * @return \Symfony\Component\Validator\Constraint
     */
    private function getSfConstraint(string $name, array $params, ?string $message)
    {        
        $constraint = null;
        
        // Constraint instances
        switch ($name) {
            /*
             * (Not) Blank validation
             */
            case 'not_blank':
                $constraint = new Constraints\NotBlank();
                break;
            /*
             * (Not) Null validation
             */
            case 'not_null':
                $constraint = new Constraints\NotNull();
                break;
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
                    'min' => (int) $params[0]
                ]);
                break;
            /*
             * String lenght validation (max)
             */
            case 'max_length':
                $constraint = new Constraints\Length([
                    'max' => (int) $params[0]
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
                $constraint = new Constraints\Email();
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
                    'min' => (int) $params[0],
                    'max' => (int) $params[1],
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
        
        // Translated error messages
        switch ($name) {
            case 'range':
                $translatedMessage = $this->translator->trans(
                    !empty($message) ? $message : $constraint->minMessage,
                    [],
                    "validators"
                );
                $constraint->minMessage = $translatedMessage;
                $constraint->maxMessage = $translatedMessage;
                break;
            case 'min_length':
                $constraint->minMessage = $this->translator->trans(
                    !empty($message) ? $message : $constraint->minMessage,
                    [],
                    "validators"
                );
                break;
            case 'max_length':
                $constraint->maxMessage = $this->translator->trans(
                    !empty($message) ? $message : $constraint->maxMessage,
                    [],
                    "validators"
                );
                break;
            default:
                $constraint->message = $this->translator->trans(
                    !empty($message) ? $message : $constraint->message,
                    [],
                    "validators"
                );
        }
       
        return $constraint;
    }
}