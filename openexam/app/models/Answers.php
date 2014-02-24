<?php




class Answers extends \Phalcon\Mvc\Model
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
    public $question_id;
     
    /**
     *
     * @var integer
     */
    public $student_id;
     
    /**
     *
     * @var string
     */
    public $answered;
     
    /**
     *
     * @var string
     */
    public $answer;
     
    /**
     *
     * @var string
     */
    public $comment;
     
    /**
     * Initialize method for model.
     */
    public function initialize()
    {
		$this->hasMany("id", "Results", "answer_id", NULL);
		$this->belongsTo("question_id", "Questions", "id", array("foreignKey"=>true));
		$this->belongsTo("student_id", "Students", "id", array("foreignKey"=>true));

    }

}
