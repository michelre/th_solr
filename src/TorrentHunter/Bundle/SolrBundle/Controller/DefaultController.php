<?php

namespace SolrServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\DiExtraBundle\Annotation as DI;
use SolrServiceBundle\Utils\Utils;

/**
 * @DI\Service("solrService")
 */
class DefaultController
{

    protected $solarium;

    /**
     * @DI\InjectParams({
     *     "solarium" = @DI\Inject("solarium.client"),
     * })
     */
    public function __construct(\Solarium\Client $solarium){
        $this->solarium = $solarium;
    }

    public function popularAction(){
        $client = $this->solarium;

        $select = array(
            'rows'  => 100,
            'sort'  => array('seeds' => 'desc')
        );
        $query = $client->createSelect($select);
        $results = $client->select($query);
        $torrents = $results->getData()['response']['docs'];

        return $torrents;
    }

    public function searchAction($queryQS, $limit, $offset){
        $client = $this->solarium;
        $service = new Utils();

        $select = array(
            'query' => 'title: "' . $queryQS . '"',
            'start' => ($offset - 1)  * $limit,
            'rows'  => $limit,
            'sort'  => array('seeds' => 'desc')
        );
        $query = $client->createSelect($select);
        $results = $client->select($query);
        $torrents = $results->getData()['response']['docs'];
        $torrents = $service->convertCategoryDocuments($torrents);
        $torrents = $service->removeFieldsDocuments($torrents, array("_version_", "_id", "score"));

        return array("torrents" => $torrents, "nbTorrentsFound" => ceil($results->getNumFound()/$limit), "query" => $queryQS);
    }

    public function torrentAction($tracker, $slug){
        $client = $this->solarium;
        $service = new Utils();

        $select = array(
            'query' => 'tracker: ' . $tracker . ', slug: "' . $slug . '"',
            'rows'  => 1,
        );
        $query = $client->createSelect($select);
        $result = $client->select($query);
        $torrent = $service->convertCategoryDocument($result->getDocuments()[0]->getFields());
        $torrent = $service->removeFieldsDocument($torrent, array("_version_", "_id", "score"));

        return $torrent;
    }

    public function searchSimilarAction($slug){
        $client = $this->solarium;
        $service = new Utils();

        $moreLikeThisQuery = $client->createMoreLikeThis();
        $moreLikeThisQuery->setQuery('slug:' . $slug);
        $moreLikeThisQuery->setMltFields("title");
        $moreLikeThisQuery->setMinimumDocumentFrequency(1);
        $moreLikeThisQuery->setMinimumTermFrequency(1);
        $moreLikeThisQuery->setMatchInclude(true);
        $resultMoreLikeThis = $client->select($moreLikeThisQuery);

        $torrents = $resultMoreLikeThis->getData()['response']['docs'];
        $torrents = $service->convertCategoryDocuments($torrents);
        $torrents = $service->removeFieldsDocuments($torrents, array('score', '_id', '_version_'));

        return $torrents;
    }

    public function torrentsByCategoryAction($category, $offset, $limit){
        $client = $this->solarium;
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        $service = new Utils();

        $select = array(
            'query' => "category:\"" . join("\" OR category:\"", $service->searchByCategory($category)) . "\"",
            'start' => ($offset - 1)  * $limit,
            'rows'  => $limit,
            'sort'  => array('seeds' => 'desc')
        );
        $query = $client->createSelect($select);
        $result = $client->select($query);
        $torrents = $service->convertCategoryDocuments($result->getData()['response']['docs']);
        $torrents = $service->removeFieldsDocuments($torrents, array("_version_", "_id", "score"));

        return array('numFound' => $result->getData()['response']['numFound'], 'torrents' => $torrents);
    }

    public function statsTrackersAction(){
        $client = $this->solarium;
        $query = $client->createSelect();
        $query->setQuery('stats:stats');
        $stats = $client->select($query);

        $nbTotal = array_reduce($stats->getData()["response"]["docs"], function($carry, $item){
            return $carry += $item["nb"];
        });

        return array("stats" => $stats->getData()["response"]["docs"], "nbTotal" => $nbTotal);
    }

}
