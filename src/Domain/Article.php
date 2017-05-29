<?php

namespace MicroCMS\Domain;

class Article 
{
    /**
     * Article id.
     *
     * @var integer
     */
    private $id;

    /**
     * Article title.
     *
     * @var string
     */
    private $title;

    /**
     * Article content.
     *
     * @var string
     */
    private $content;
    
    /**
     * Date of creation.
     *
     * @var string
     */
    private $articleDate;
    
    /**
     * If the article is publied.
     *
     * @var boolean
     */
    private $publied;

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
        return $this;
    }
    
    public function getArticleDate() {
        return $this->articleDate;
    }
    
    public function setArticleDate($date) {
        $this->articleDate = $date;
        return $this;
    }
    
    public function getPublied() {
        return $this->publied;
    }
    
    public function setPublied($boolean) {
        $this->publied = $boolean;
        return $this;
    }
}
