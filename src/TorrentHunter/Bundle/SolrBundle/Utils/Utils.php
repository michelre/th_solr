<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 17/01/15
 * Time: 22:27
 */

namespace SolrServiceBundle\Utils;

use Cocur\Slugify\Slugify;


class Utils {

    private function allCategories(){
        $movieCategories = array(
            "Films" => "movie",
            "DVDRip" => "movie",
            "BDRip" => "movie",
            "HD 1080p" => "movie",
            "HD 720p" => "movie",
            "VOSTFR" => "movie",
            "Screener" => "movie",
            "R5 / DVDScreener" => "movie",
            "films" => "movie"
        );
        $musicCategories = array(
            "Albums" => "music",
            "Musique" => "music",
        );
        $serieCategories = array(
            "Séries" => "serie",
            "Série" => "serie",
            "Série VF" => "serie",
            "Série VOSTFR" => "serie",
            "Pack Série" => "serie"
        );
        $gameCategories = array(
            "Jeux PC" => "game",
            "Jeux Consoles" => "game"
        );
        $softwareCategories = array(
            "Logiciels" => "application",
            "Application" => "application"
        );
        $ebooksCategories = array(
            "Ebooks" => "ebook",
            "Ebook" => "ebook"
        );

        return array_merge($movieCategories, $musicCategories, $serieCategories, $gameCategories, $softwareCategories, $ebooksCategories);
    }

    public function searchByCategory($category){
        return array_keys($this->allCategories(), $category);
    }

    public function convertCategory($category){
        $categories = $this->allCategories();

        return (array_key_exists($category, $categories)) ? $categories[$category] : $category;
    }

    public function convertCategoryDocument($document){

        $document['category'] = (array_key_exists('category', $document)) ? $this->convertCategory($document['category']) : "unknown";
        return $document;
    }

    public function convertCategoryDocuments($documents){
        return array_map(function($document){
            return $this->convertCategoryDocument($document);
        }, $documents);
    }

    public function removeFieldsDocument($document, $notUsedKeys){
        foreach($notUsedKeys as $k){
            unset($document[$k]);
        }
        return $document;
    }

    public function removeFieldsDocuments($documents, $notUsedKeys){
        return array_map(function($document) use(&$notUsedKeys){
            return $this->removeFieldsDocument($document, $notUsedKeys);
        }, $documents);
    }

} 