<?php

namespace MicroCMS\Domain;

class Comment 
{
    /**
     * Comment id.
     *
     * @var integer
     */
    private $id;

    /**
     * Comment author.
     *
     * @var \MicroCMS\Domain\User
     */
    private $author;

    /**
     * Comment content.
     *
     * @var integer
     */
    private $content;

    /**
     * Associated article.
     *
     * @var \MicroCMS\Domain\Article
     */
    private $article;
    
    /**
     * Array of user id.
     *
     * @var array
     */
    private $reports = NULL;
    
    /**
     * Store a comment id.
     *
     * @var number id
     */
    private $childOf = NULL;
    
    /**
     * contain all children comments id.
     *
     * @var array of \MicroCMS\Domain\Comment
     */
    private $parentOf = NULL;
    
    /**
     * Date of creation.
     *
     * @var string 
     */
    private $commentDate;
    
    function __construct($article = NULL, $user = NULL) {
        $this->setCommentDate(date("d/m/Y à H:i"))
            ->setArticle($article)
            ->setAuthor($user);
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
        return $this;
    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    public function getArticle() {
        return $this->article;
    }

    public function setArticle($article) {
        $this->article = $article;
        return $this;
    }
    
    public function getReports($option, $userId = NULL) {
            switch (true) {
                case ($option == 'array'):
                    if ($this->reports == NULL) {
                        return array();
                    } else {
                        return $this->reports;
                    }
                    break;
                case ($option == 'json'):
                    if ($this->reports == NULL) {
                        return NULL;
                    } else {
                        return json_encode($this->reports);
                    }
                    break;
                case ($option == 'number'):
                    return count($this->reports);
                    break;
                case ($option == 'reportedBy' && isset($userId)):
                    if (is_array($this->reports)) {
                        return in_array($userId, $this->reports);
                    } else {
                        return false;
                    }
                default:
                    return $this->reports; //array
            }
    }
    
    public function setReports($option, $userId) {
        switch ($option) {
            case 'add':
                $array = $this->getReports('array');
                if (in_array($userId, $array) == false) {
                    array_push($array, $userId);
                    $this->reports = $array;
                }
                return $this;
                break;
            
            case 'suppr':
                if (in_array($userId, $this->reports)) {
                    unset($this->reports[array_search($userId, $this->reports)]);
                    return $this;
                } else {
                    echo 'This user doesnt exist';
                }
                break;
            
            case 'as':
                $this->reports = $userId;
                return $this;
                break;
            
            default: 
                echo 'Wrong option';
        }
        
    }
    
    public function getChildOf() {
        return $this->childOf;
    }
    
    public function setChildOf($id) {
        $this->childOf = $id;
        return $this;
    }
    
    public function getParentOf($option) {
        switch ($option) {
            case 'array': //arrayObject
                return $this->parentOf;
                break;
            case 'json': //jsonId
                $idArray = [];
                $objectArray = $this->parentOf;
        
                if ($objectArray !== [] && $objectArray !== null) {
                    foreach($objectArray as $key => $object) {
                    array_push($idArray, $object->getId());
                    }
                    return json_encode($idArray);
                } else {
                    return NULL;
                }
                break;
            default:
                return $this->parentOf; //arrayObject
        }
            
    }
    
    public function setParentOf($childComments) {
        $this->parentOf = $childComments;
        return $this;
    }
    
    public function getCommentDate() {
        return $this->commentDate;
    }
    
    public function setCommentDate($date) {
        $this->commentDate = $date;
        return $this;
    }
}
