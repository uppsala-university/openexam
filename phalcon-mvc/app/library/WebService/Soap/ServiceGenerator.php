<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceGenerator.php
// Created: 2014-10-16 16:10:28
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Soap;

use OpenExam\Models\ModelBase;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Mvc\Model\Relation;

/**
 * Type base class.
 */
class Type
{

        /**
         * The name.
         * @var string 
         */
        public $name;
        /**
         * The type.
         * @var string 
         */
        public $type;
        /**
         * The doc comment.
         * @var string 
         */
        public $comment;

        /**
         * Constructor.
         * @param string $name The name.
         * @param string $type The type.
         * @param string $comment Optional doc comment.
         */
        public function __construct($name, $type, $comment = "")
        {
                $this->name = $name;
                $this->type = $type;
                $this->comment = $comment;
        }

}

/**
 * Class member variable.
 */
class Member extends Type
{

        /**
         * The access restriction.
         * @var string 
         */
        public $access = 'private';

        /**
         * Set access restriction.
         * @param string $access The access restriction.
         */
        public function setAccess($access)
        {
                $this->access = $access;
        }

        public function __toString()
        {
                $result = "";

                $result .= "\t/**\n";
                $result .= "\t * " . $this->comment . "\n";
                $result .= "\t * @var " . $this->type . "\n";
                $result .= "\t */\n";
                $result .= "\t " . $this->access . " \$" . $this->name . ";\n";

                return $result;
        }

}

/**
 * Method parameter.
 */
class Parameter extends Type
{

        public function __toString()
        {
                return sprintf("@param %s \$%s %s", $this->type, $this->name, $this->comment);
        }

}

/**
 * Return value.
 */
class Result extends Type
{

        /**
         * Constructor.
         * @param string $type The type.
         * @param string $comment Optional doc comment.
         */
        public function __construct($type, $comment = "")
        {
                parent::__construct(null, $type, $comment);
        }

        public function __toString()
        {
                return sprintf("@return %s %s", $this->type, $this->comment);
        }

}

/**
 * Exception throwed. Use name as alias.
 */
class Throws extends Type
{

        public function __construct($type, $comment = "")
        {
                parent::__construct(null, $type, $comment);
        }

        public function __toString()
        {
                return sprintf("@throws %s", $this->type);
        }

}

/**
 * Class method.
 */
class Method
{

        /**
         * Method parameters.
         * @var Parameter[] 
         */
        public $params;
        /**
         * The return type.
         * @var Result 
         */
        public $return;
        /**
         * The method name.
         * @var string 
         */
        public $name;
        /**
         * The doc comment.
         * @var string 
         */
        public $comment;
        /**
         * The method body (code).
         * @var string 
         */
        public $code = "";
        /**
         * Access restriction for this method.
         * @var string 
         */
        public $access = 'public';
        /**
         * Throwed exceptions.
         * @var Throws[] 
         */
        public $throws = array();

        /**
         * Constructor.
         * @param string $name The method name.
         * @param Parameter[] $params The method parameters.
         * @param Result $return The return type.
         */
        public function __construct($name, $params = array(), $return = null, $comment = "")
        {
                $this->name = $name;
                $this->params = $params;
                $this->return = $return;
                $this->comment = $comment;
        }

        /**
         * Add method parameter.
         * @param Parameter $param The method parameter.
         */
        public function addParam($param)
        {
                $this->params[] = $param;
        }

        /**
         * Set return type.
         * @param Result $return The return type.
         */
        public function setReturn($return)
        {
                $this->return = $return;
        }

        /**
         * Set doc comment.
         * @param string $comment The doc comment.
         */
        public function setComment($comment)
        {
                $this->comment = $comment;
        }

        /**
         * Set method body (code).
         * @param string $code The method code.
         */
        public function setCode($code)
        {
                $this->code = $code;
        }

        /**
         * Add code to method body.
         * @param string $code The method code.
         */
        public function addCode($code)
        {
                $this->code .= "\n" . $code;
        }

        /**
         * Set access restriction.
         * @param string $access The access restriction.
         */
        public function setAccess($access)
        {
                $this->access = $access;
        }

        /**
         * Add exception throwed.
         * @param Throws $throw The exception type.
         */
        public function addThrows($throw)
        {
                $this->throws[] = $throw;
        }

        public function __toString()
        {
                $result = "";

                // 
                // Print doc comment:
                // 
                $result .= "\t/**\n";
                if (isset($this->comment)) {
                        $result .= "\t * " . $this->comment . PHP_EOL;
                }
                foreach ($this->params as $param) {
                        $result .= "\t * " . (string) $param . PHP_EOL;
                }
                if (isset($this->return)) {
                        $result .= "\t * " . (string) $this->return . PHP_EOL;
                }
                foreach ($this->throws as $throw) {
                        $result .= "\t * " . (string) $throw . PHP_EOL;
                }
                $result .= "\t */\n";

                // 
                // Print method:
                // 
                $result .= "\t" . $this->access . " function " . $this->name . "(";
                $params = array();
                foreach ($this->params as $param) {
                        $params[] = "\$" . $param->name;
                }
                $result .= implode(", ", $params);
                $result .= ")\n";
                $result .= "\t{\n";
                $result .= $this->code . "\n";
                $result .= "\t}\n";

                return $result;
        }

}

/**
 * Service class. 
 */
class ServiceClass
{

        /**
         * The class name.
         * @var string 
         */
        public $name;
        /**
         * Class members.
         * @var Member[] 
         */
        public $members = array();
        /**
         * Class methods.
         * @var Method[] 
         */
        public $methods;
        /**
         * The doc comment.
         * @var string 
         */
        public $comment;
        /**
         * The class to extends (if any).
         * @var string 
         */
        public $extends;
        /**
         * Implemented interfaces.
         * @var array 
         */
        public $implements = array();
        /**
         * Any use types.
         * @var array 
         */
        public $use = array();
        /**
         * The class namespace.
         * @var string 
         */
        public $namespace;
        /**
         * The copyright notice (string or file path).
         * @var string 
         */
        public $copyright;

        /**
         * Constructor.
         * @param string $name The class name.
         * @param Method[] $methods The class methods.
         * @param string $comment The doc comment.
         */
        public function __construct($name = "", $methods = array(), $comment = "")
        {
                $this->name = $name;
                $this->methods = $methods;
                $this->comment = $comment;
        }

        /**
         * Set class level doc comment.
         * @param string $comment The doc comment.
         */
        public function setComment($comment)
        {
                $this->comment = $comment;
        }

        /**
         * Set the name of generated class.
         * @param string $name The class name.
         */
        public function setName($name)
        {
                $this->name = $name;
        }

        /**
         * Set copyright notice.
         * @param string $copyright The copyright notice.
         */
        public function setCopyright($copyright)
        {
                if (file_exists($copyright)) {
                        $this->copyright = file_get_contents($copyright);
                } else {
                        $this->copyright = $copyright;
                }
        }

        /**
         * Set the PHP namespace of generated class.
         * @param string $namespace The namespace.
         */
        public function setNamespace($namespace)
        {
                $this->namespace = $namespace;
        }

        /**
         * Set class to extend.
         * @param string $class
         */
        public function setExtends($class)
        {
                $this->extends = $class;
        }

        /**
         * Add interface to implement.
         * @param string $interface The interface to implement.
         */
        public function addImplements($interface)
        {
                if (!in_array($interface, $this->implements)) {
                        $this->implements[] = $interface;
                }
        }

        /**
         * Add type to use.
         * @param string $type The class type.
         */
        public function addUse($type, $alias = null)
        {
                if (!isset($this->use[$type])) {
                        $this->use[$type] = $alias;
                }
        }

        /**
         * Add method to service class.
         * @param Method $method The method object.
         */
        public function addMethod($method)
        {
                if (!isset($this->methods[$method->name])) {
                        $this->methods[$method->name] = $method;
                }
        }

        /**
         * Add member variable.
         * @param Member $member The member variable.
         */
        public function addMember($member)
        {
                if (!isset($this->members[$member->name])) {
                        $this->members[$member->name] = $member;
                }
        }

        /**
         * Write this class to file.
         * @param string $filename
         */
        public function save($filename)
        {
                file_put_contents($filename, (string) $this);
        }

        /**
         * Dump this class to stdout.
         */
        public function dump()
        {
                echo (string) $this;
        }

        public function __toString()
        {
                $result = "<?php\n\n";

                // 
                // Sort use types, members and methods:
                // 
                ksort($this->use);
                ksort($this->members);
                ksort($this->methods);

                // 
                // Add copyright if set:
                // 
                if (isset($this->copyright)) {
                        $result .= $this->copyright . PHP_EOL;
                }

                // 
                // Add namespace and use statements:
                // 
                if (isset($this->namespace)) {
                        $result .= "namespace " . $this->namespace . ";\n\n";
                }
                if (count($this->use) != 0) {
                        foreach ($this->use as $type => $alias) {
                                if (isset($alias)) {
                                        $result .= "use " . $type . " as " . $alias . ";\n";
                                } else {
                                        $result .= "use " . $type . ";\n";
                                }
                        }
                        $result .= "\n";
                }

                // 
                // Add doc comment:
                // 
                $result .= "/**\n";
                $result .= " * " . $this->comment . PHP_EOL;
                $result .= " */\n";

                // 
                // Add class ingress:
                // 
                $result .= "class " . $this->name;
                if (isset($this->extends)) {
                        $result .= " extends " . $this->extends;
                }
                if (count($this->implements) != 0) {
                        $result .= " implements " . implode(",", $this->implements);
                }
                $result .= "\n";
                $result .= "{\n";

                // 
                // Add class members:
                // 
                foreach ($this->members as $member) {
                        $result .= (string) $member . "\n";
                }

                // 
                // Add class methods:
                // 
                foreach ($this->methods as $method) {
                        $result .= (string) $method . "\n";
                }

                // 
                // We are done.
                // 
                $result .= "}\n";
                return $result;
        }

}

/**
 * Generate SOAP service class.
 *
 * <code>
 * // 
 * // Generate SOAP service with handle:
 * // 
 * $resources = array(
 *      'answer' => array('create','read','update','delete')
 * );
 * $generator = new ServiceGenerator(null, array('with-handle' => true));
 * $generator->generate($resources);
 * 
 * // 
 * // Save service class to file:
 * // 
 * $service = $generator->getService();
 * $service->save('AnswerService.php');
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ServiceGenerator
{

        /**
         * Options for service generation.
         * @var array 
         */
        private $options;
        /**
         * The resources to generate methods for.
         * @var array 
         */
        private $resources;
        /**
         * The service class.
         * @var ServiceClass 
         */
        private $service;

        /**
         * Constructor.
         * @param array $resources The resource to actions map.
         * @param array $options Service generation options.
         */
        public function __construct($resources, $options)
        {
                $this->resources = $resources;
                $this->options = $options;
                $this->service = new ServiceClass();
        }

        /**
         * Set the service class.
         * @param ServiceClass $class The service class.
         */
        public function setService($class)
        {
                $this->service = $class;
        }

        /**
         * Get the service class.
         * @return ServiceClass
         */
        public function getService()
        {
                return $this->service;
        }

        /**
         * Start service generation.
         * @param array $resources The resource to actions map.
         */
        public function generate($resources = null, $name = "")
        {
                if (!isset($this->service)) {
                        $this->service = new ServiceClass($name);
                }
                if (!isset($this->service->name)) {
                        $this->service->setName('Generated' . date('Ymd'));
                }
                if (isset($resources)) {
                        $this->resources = $resources;
                }

                $this->service->setComment(
                    sprintf("This class was generated by %s\n * Date: %s", __CLASS__, strftime("%x %X"))
                );


                // 
                // Add standard methods:
                // 
                $this->service->addMethod($this->getConstructor());
                $this->service->addMethod($this->getMethodInitialize());

                if ($this->options['with-handle']) {
                        $this->service->addMethod($this->getMethodOpenExam());
                }

                // 
                // Add resource access methods for all requested actions:
                // 
                foreach ($this->resources as $resource => $actions) {
                        $relations = $this->getRelations($resource);
                        foreach ($actions as $action) {
                                switch ($action) {
                                        case ObjectAccess::CREATE:
                                                $this->addCreate($resource, $relations);
                                                break;
                                        case ObjectAccess::READ:
                                                $this->addRead($resource, $relations);
                                                break;
                                        case ObjectAccess::UPDATE:
                                                $this->addUpdate($resource);
                                                break;
                                        case ObjectAccess::DELETE:
                                                $this->addDelete($resource);
                                                break;
                                }
                        }
                }
        }

        /**
         * Add read method(s) for this resource.
         * @param string $resource The resource name.
         * @param Relation[] The array of resource relations.
         */
        private function addRead($resource, $relations)
        {
                $restype = ModelBase::getRelation($resource);
                $this->service->addUse($restype);
                $this->service->addUse('OpenExam\Plugins\Security\Model\ObjectAccess');
                $resname = trim(strrchr($restype, '\\'), '\\');

                // 
                // The getTypes() method:
                // 
                $method = new Method(sprintf("get%ss", ucfirst($resource)));
                $method->setComment("Read list of ${resource}s.");
                $this->addInitilize($method);
                $method->addCode(
                    <<< EOL
                \$model = new $resname();
EOL
                );
                $this->addRelations($method, $relations, '$model');
                $method->setReturn(new Result("${restype}[]", "The $resource list."));
                $method->addCode(
                    <<< EOL
                return \$this->core->action(\$model, ObjectAccess::READ);
EOL
                );
                $this->service->addMethod($method);

                // 
                // The getType($id) method:
                // 
                $method = new Method(sprintf("get%s", ucfirst($resource)));
                $method->setComment("Read single ${resource}.");
                $this->addInitilize($method);
                $method->addParam(new Parameter("id", "int", "The $resource ID."));
                $method->setReturn(new Result("${restype}", "The $resource object."));
                $method->addCode(
                    <<< EOL
                \$model = new $resname();
                \$model->id = \$id;
                return \$this->core->action(\$model, ObjectAccess::READ);
EOL
                );
                $this->service->addMethod($method);
        }

        /**
         * Add create method(s) for this resource.
         * @param string $resource The resource name.
         * @param Relation[] The array of resource relations.
         */
        private function addCreate($resource, $relations)
        {
                $restype = ModelBase::getRelation($resource);
                $this->service->addUse($restype);
                $this->service->addUse('OpenExam\Plugins\Security\Model\ObjectAccess');
                $resname = trim(strrchr($restype, '\\'), '\\');

                // 
                // The addTypes(type[]) method:
                // 
                $method = new Method(sprintf("add%ss", ucfirst($resource)));
                $method->setComment("Add list of ${resource}s.");
                $this->addInitilize($method);
                $this->addRelations($method, $relations, "\$${resource}", "${resource}s");
                $method->addParam(new Parameter("${resource}s", "${restype}[]", "The list of ${resource} object."));
                $method->setReturn(new Result("${restype}[]", "The list of created ${resource}s."));
                $method->addCode(
                    <<< EOL
                return \$this->core->action(\$${resource}s, ObjectAccess::CREATE);
EOL
                );
                $this->service->addMethod($method);

                // 
                // The addType(type) method:
                // 
                $method = new Method(sprintf("add%s", ucfirst($resource)));
                $method->setComment("Add single ${resource}.");
                $this->addInitilize($method);
                $this->addRelations($method, $relations, "\$${resource}");
                $method->addParam(new Parameter($resource, $resname, "The ${resource} object."));
                $method->setReturn(new Result("int", "The created $resource ID."));
                $method->addCode(
                    <<< EOL
                \$result = \$this->core->action(\$${resource}, ObjectAccess::CREATE);
                return \$result->id;
EOL
                );
                $this->service->addMethod($method);
        }

        /**
         * Add update method(s) for this resource.
         * @param string $resource The resource name.
         */
        private function addUpdate($resource)
        {
                $restype = ModelBase::getRelation($resource);
                $this->service->addUse($restype);
                $this->service->addUse('OpenExam\Plugins\Security\Model\ObjectAccess');
                $resname = trim(strrchr($restype, '\\'), '\\');

                // 
                // The setType(type) method:
                // 
                $method = new Method(sprintf("set%s", ucfirst($resource)));
                $method->setComment("Edit (update/set) ${resource}.");
                $this->addInitilize($method);
                $method->addParam(new Parameter($resource, $resname, "The ${resource} object."));
                $method->setReturn(new Result("boolean", "True if successful."));
                $method->addCode(
                    <<< EOL
                return \$this->core->action(\$${resource}, ObjectAccess::UPDATE);
EOL
                );
                $this->service->addMethod($method);
        }

        /**
         * Add delete method(s) for this resource.
         * @param string $resource The resource name.
         */
        private function addDelete($resource)
        {
                $restype = ModelBase::getRelation($resource);
                $this->service->addUse($restype);
                $this->service->addUse('OpenExam\Plugins\Security\Model\ObjectAccess');
                $resname = trim(strrchr($restype, '\\'), '\\');

                // 
                // The deleteType(type) method:
                // 
                $method = new Method(sprintf("delete%s", ucfirst($resource)));
                $method->setComment("Delete ${resource}.");
                $this->addInitilize($method);
                $method->addParam(new Parameter('id', 'int', "The ${resource} ID."));
                $method->setReturn(new Result("boolean", "True if successful."));
                $method->addCode(
                    <<< EOL
                \$model = new $resname();
                \$model->id = \$id;
                return \$this->core->action(\$model, ObjectAccess::DELETE);
EOL
                );
                $this->service->addMethod($method);
        }

        /**
         * Add initialize call to method.
         * @param Method $method The method to add code to.
         */
        private function addInitilize($method)
        {
                $this->service->addUse('OpenExam\Library\WebService\Soap\Types\Handle');

                if ($this->options['with-handle']) {
                        $method->addParam(new Parameter('handle', 'Handle', 'The handle object.'));
                        $method->setCode(
                            <<< EOL
                \$this->initialize(\$handle);
EOL
                        );
                } else {
                        $role = $this->options['role'];
                        $method->setCode(
                            <<< EOL
                \$this->initialize(new Handle('$role'));
EOL
                        );
                }
        }

        /**
         * Add relations assignment to method.
         * @param Method $method The method to add code to.
         * @param Relation[] $relations The relations array.
         * @param string $model The model object name.
         * @param string $models The models array name.
         */
        private function addRelations($method, $relations, $model, $models = null)
        {
                if (count($relations) != 0) {
                        foreach ($relations as $relation) {
                                $field = $relation->getFields();
                                $alias = $relation->getOptions()['alias'];
                                $method->addParam(new Parameter($alias, "int", "The $alias ID."));
                                if (isset($models)) {
                                        $method->addCode(
                                            <<< EOL
                foreach ($models as $model) {
                        if (\$$alias != 0) {
                                $model->$field = \$$alias;
                        }
                }
EOL
                                        );
                                } else {
                                        $method->addCode(
                                            <<< EOL
                if (\$$alias != 0) {
                        $model->$field = \$$alias;
                }
EOL
                                        );
                                }
                        }
                }
        }

        /**
         * Get relations for resource.
         * @param string $resource
         * @return Relation
         */
        private function getRelations($resource)
        {
                $class = ModelBase::getRelation($resource);
                $model = new $class();
                return $model->getModelsManager()->getBelongsTo($model);
        }

        /**
         * Get constructor().
         * @return Method
         */
        private function getConstructor()
        {
                $method = new Method("__construct");

                // 
                // Add authenticated user member:
                // 
                $this->service->addMember(new Member('user', 'User', 'The authenticated user.'));
                $this->service->addUse('OpenExam\Library\Security\User');

                $this->service->addMember(new Member('core', 'CoreHandler', 'The core handler object.'));
                $this->service->addUse('OpenExam\Library\Core\Handler\CoreHandler');

                $method->setComment('Constructor');
                $method->addParam(new Parameter('user', 'User', 'The authenticated user.'));
                $method->setCode(
                    <<< EOL
                \$this->user = \$user;
EOL
                );

                return $method;
        }

        /**
         * Get initialize() method.
         * @return Method
         */
        private function getMethodInitialize()
        {
                $method = new Method('initialize');

                $this->service->addUse('OpenExam\Library\Security\Exception', 'SecurityException');
                $this->service->addUse('OpenExam\Library\WebService\Soap\Types\Handle');

                $method->setAccess('private');
                $method->setComment('Initialize function for SOAP call.');
                $method->addParam(new Parameter('handle', 'Handle'));
                $method->addThrows(new Throws('SecurityException', 'Exception throwed if role is missing.'));
                $method->setCode(
                    <<< EOL
                if (\$this->user->getUser() == null) {
                        throw new SecurityException("User is not authenticated.");
                }
                
                if (!isset(\$handle->role) || strlen(\$handle->role) == 0) {
                        throw new SecurityException("The requested role is not set.");
                } elseif (\$this->user->roles->aquire(\$handle->role) == false) {
                        throw new SecurityException("Failed aquire requested role.");
                } else {
                        \$this->user->setPrimaryRole(\$handle->role);
                }
                    
                if (!isset(\$this->core)) {
                        \$this->core = new CoreHandler(\$handle->role);
                }
                
                return \$handle;
EOL
                );

                return $method;
        }

        /**
         * Get openExam() method.
         * @return Method
         */
        private function getMethodOpenExam()
        {
                $method = new Method("openExam");

                $this->service->addUse('OpenExam\Library\WebService\Soap\Types\Handle');

                $method->setComment("Open SOAP service handle for subsequent calls.");
                $method->setReturn(new Result("Handle", "The handle object (return)."));
                $method->addParam(new Parameter('handle', 'Handle', 'The handle object (input).'));
                $method->setCode(
                    <<< EOL
                return \$this->initialize(\$handle);
EOL
                );

                return $method;
        }

}
