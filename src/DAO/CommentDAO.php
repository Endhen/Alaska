<?php

namespace MicroCMS\DAO;

use MicroCMS\Domain\Comment;

class CommentDAO extends DAO 
{
    /**
     * @var \MicroCMS\DAO\ArticleDAO
     */
    private $articleDAO;

    /**
     * @var \MicroCMS\DAO\UserDAO
     */
    private $userDAO;

    public function setArticleDAO(ArticleDAO $articleDAO) {
        $this->articleDAO = $articleDAO;
    }

    public function setUserDAO(UserDAO $userDAO) {
        $this->userDAO = $userDAO;
    }

    /**
     * Returns a list of all comments, sorted by date (most recent first).
     *
     * @return array A list of all comments.
     */
    public function findAll() {
        $sql = "select * from t_comment order by com_id desc";
        $result = $this->getDb()->fetchAll($sql);

        // Convert query result to an array of domain objects
        $entities = array();
        foreach ($result as $row) {
            $id = $row['com_id'];
            $entities[$id] = $this->buildDomainObject($row);
        }
        return $entities;
    }

    /**
     * Return a list of all comments for an article, sorted by date (most recent last).
     *
     * @param integer $articleId The article id.
     *
     * @return array A list of all comments for the article.
     */
    public function findAllByArticle($articleId) {
        // The associated article is retrieved only once
        $article = $this->articleDAO->find($articleId);

        // art_id is not selected by the SQL query
        // The article won't be retrieved during domain objet construction
        $sql = "select * from t_comment where art_id=? order by com_id";
        $result = $this->getDb()->fetchAll($sql, array($articleId));

        // Convert query result to an array of domain objects
        $comments = array();
        foreach ($result as $row) {
            $comId = $row['com_id'];
            $comment = $this->buildDomainObject($row);
            // The associated article is defined for the constructed comment
            $comment->setArticle($article);
            $comments[$comId] = $comment;
        }
        return $comments;
    }
    
    
    /**
     * Return a list of all reported comments.
     *
     * @return array A list of all reported comments.
     */
    public function findAllReported() {
        $sql = "select * from t_comment where reports IS NOT NULL order by reports desc";
        // Array of comment array
        $result = $this->getDb()->fetchAll($sql);
        // Convert query result to an array of domain objects
        $comments = [];
        
        foreach ($result as $row) {
            $comId = $row['com_id'];
            $comment = $this->buildDomainObject($row);
            $comments[$comId] = $comment;
        }
        return $comments;
    }

    /**
     * Returns a comment matching the supplied id.
     *
     * @param integer $id The comment id
     *
     * @return \MicroCMS\Domain\Comment|null - option(throws an exception if no matching comment is found)
     */
    public function find($id) {
        $sql = "select * from t_comment where com_id=?";
        $row = $this->getDb()->fetchAssoc($sql, array($id));

        if ($row)
            return $this->buildDomainObject($row);
        else
            return null;
            //throw new \Exception("No comment matching id " . $id);
    }
    
    /**
     * find the latest comment into the database.
     *
     * @return \MicroCMS\Domain\Comment 
     */
    public function findLatest() {
        $sql = "select * from t_comment order by com_id desc limit 1";
        $row = $this->getDb()->fetchAssoc($sql);
        
        return $this->buildDomainObject($row);
    }

    /**
     * Saves a comment into the database.
     *
     * @param \MicroCMS\Domain\Comment $comment The comment to save
     */
    public function save(Comment $comment) {
        
        
        $commentData = array(
            'art_id' => $comment->getArticle()->getId(),
            'usr_id' => $comment->getAuthor()->getId(),
            'com_content' => $comment->getContent(),
            'childOf' => $comment->getChildOf(),
            'parentOf' => $comment->getParentOf('json'),
            'reports' => $comment->getReports('json'),
            'com_date' => $comment->getCommentDate()
            );

        if ($comment->getId()) {
            // The comment has already been saved : update it
            $this->getDb()->update('t_comment', $commentData, array('com_id' => $comment->getId()));
        } else {
            // The comment has never been saved : insert it
            $this->getDb()->insert('t_comment', $commentData);
            // Get the id of the newly created comment and set it on the entity.
            $id = $this->getDb()->lastInsertId();
            $comment->setId($id);
        }
    }

    /**
     * Removes all comments for an article
     *
     * @param integer $articleId The id of the article
     */
    public function deleteAllByArticle($articleId) {
        $this->getDb()->delete('t_comment', array('art_id' => $articleId));
    }

    /**
     * Removes all comments for a user
     *
     * @param integer $userId The id of the user
     */
    public function deleteAllByUser($userId) {
        $this->getDb()->delete('t_comment', array('usr_id' => $userId));
    }

    /**
     * Removes a comment from the database.
     *
     * @param integer $id The comment id
     */
    public function delete($id) {
        
        // Delete child of comment
        $commentChilds = $this->find($id)->getParentOf('array');
        
        if ($commentChilds !== [] && $commentChilds !== NULL) {
            foreach($commentChilds as $key => $child) {
                 $this->delete($child->getId());
            }
        }
        
        // Delete the comment
        $this->getDb()->delete('t_comment', array('com_id' => $id));
    }
    
    /**
     * Creates an array of comments based on a DB row.
     *
     * @param json of all child comment's id
     * @return array of \MicroCMS\Domain\Comment
     */
    protected function jsonTranscript($json) {
        // JSON to array
        $idArray = json_decode($json, true);
        $childComments = [];
        
        if (empty($idArray)) {
            return $childComments;
        } else {
            
            // push a new object in array 
            foreach($idArray as $key => $id) {
                $comment = $this->find($id);
                //verify if the comment exist
                if ($comment !== null) {
                    array_push($childComments, $comment);
                }
            }
            return $childComments;
        }
    }
    
    /**
     * Creates an Comment object based on a DB row.
     *
     * @param array $row The DB row containing Comment data.
     * @return \MicroCMS\Domain\Comment
     */
    protected function buildDomainObject(array $row) {
        $comment = new Comment();
        $comment
            ->setId($row['com_id'])
            ->setContent($row['com_content'])
            ->setChildOf($row['childOf'])
            ->setCommentDate($row['com_date'])
            ->setReports('as', json_decode($row['reports'], true))
            ->setParentOf($this
                          ->jsonTranscript($row['parentOf']));
        
        if (array_key_exists('art_id', $row)) {
            // Find and set the associated article
            $articleId = $row['art_id'];
            $article = $this->articleDAO->find($articleId);
            $comment->setArticle($article);
        }
        
        if (array_key_exists('usr_id', $row)) {
            // Find and set the associated author
            $userId = $row['usr_id'];
            $user = $this->userDAO->find($userId);
            $comment->setAuthor($user);
        }
        
        return $comment;
    }
}
