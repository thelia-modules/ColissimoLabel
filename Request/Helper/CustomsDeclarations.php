<?php


namespace ColissimoLabel\Request\Helper;


class CustomsDeclarations
{
    protected $includeCustomsDeclarations = false;

    protected $articles = [];

    protected $category = 3;

    public function __construct($includeCustomsDeclarations, $category, $articles)
    {
        $this->includeCustomsDeclarations = $includeCustomsDeclarations;
        $this->category = $category;
        $this->articles = $articles;
    }

    /**
     * @return bool|string
     */
    public function getIncludeCustomsDeclarations()
    {
        if (!$this->includeCustomsDeclarations) {
            return 'false';
        }
        return $this->includeCustomsDeclarations;
    }

    /**
     * @param $includeCustomsDeclarations
     * @return $this
     */
    public function setIncludeCustomsDeclarations($includeCustomsDeclarations)
    {
        $this->includeCustomsDeclarations = $includeCustomsDeclarations;
        return $this;
    }

    /**
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param $category
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return array
     */
    public function getArticles()
    {
        return $this->articles;
    }

    /**
     * @param $articles
     * @return $this
     */
    public function setArticles($articles)
    {
        $this->articles[] = $articles;
        return $this;
    }
}