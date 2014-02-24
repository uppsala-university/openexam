<?php




class Topics extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $id;
     
    /**
     *
     * @var integer
     */
    public $exam_id;
     
    /**
     *
     * @var string
     */
    public $name;
     
    /**
     *
     * @var integer
     */
    public $randomize;
     
    /**
     * Initialize method for model.
     */
    public function initialize()
    {
		$this->hasMany("id", "Questions", "topic_id", NULL);

    }

}
