<?php

namespace OpenExam\Models;

use Phalcon\Mvc\Model;

/**
 * Base class for all models.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ModelBase extends Model
{

        protected function initialize()
        {
                $this->setReadConnectionService('dbread');
                $this->setWriteConnectionService('dbwrite');
        }

        /**
         * Get model resource name.
         * @return string
         */
        public function getResourceName()
        {
                return strtolower(substr(strrchr(get_class($this), "\\"), 1));
        }

        /**
         * Get relation map.
         * @param string $resource The resource name.
         * @param string $leftcol The left hand column.
         * @param string $rightcol The right hand column.
         * @return string
         */
        public static function getRelation($resource, $leftcol = null, $rightcol = null)
        {
                if (isset($rightcol)) {
                        return __NAMESPACE__ . '\\' . ucfirst($resource) . '.' . $leftcol . '=' . $rightcol;
                } elseif (isset($leftcol)) {
                        return __NAMESPACE__ . '\\' . ucfirst($resource) . '.' . $leftcol;
                } else {
                        return __NAMESPACE__ . '\\' . ucfirst($resource);
                }
        }

        protected function afterFetch()
        {
                $modelsManager = $this->getModelsManager();
                $eventsManager = $modelsManager->getEventsManager();
                $eventsManager->fire('model:afterFetch', $this);
        }

        protected function beforeValidationOnUpdate()
        {
                // 
                // Check that required attributes are set:
                // 
                $required = $this->getModelsMetaData()->getNotNullAttributes($this);

                foreach ($required as $attr) {
                        if (!isset($this->$attr)) {
                                $populate = true;
                                break;
                        }
                }

                if (!isset($populate)) {
                        return;
                }

                $class = get_class($this);
                $model = $class::findFirst("id = $this->id");

                foreach ($required as $attr) {
                        if (!isset($this->$attr)) {
                                $this->$attr = $model->$attr;
                        }
                }

                // 
                // Now it gets even more ugly:
                // 
                $attributes = get_object_vars($this);
                foreach ($attributes as $attr => $value) {
                        if (!isset($this->$attr) && isset($model->$attr)) {
                                $this->$attr = $model->$attr;
                        }
                }
        }

}
