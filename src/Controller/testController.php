<?php

namespace App\Controller;

use App\Entity\Projet;
use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\callAPI;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;
use  \Spipu\Html2Pdf\Html2Pdf;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;


class testController  extends  AbstractController {


    /**
     * @var callAPI
     */
    private $API;

//    public function __construct(callAPI $API) {
//
//    $this-> API = $API;
//
//    }

//    public function getconnex() {
//
//
//    }

    /**
     * @Route("/", name="firstPage")
     */
    public function getProjects() :Response
    {
        $callApi = new callAPI();
        $method = 'GET';
        $apiKey = 'd07b51bb74af39ccbf3d59a0e8d833033dfa3b37' ;
        $url = 'http://localhost/dolibarr/htdocs/api/index.php/projects?sortfield=t.rowid&sortorder=ASC&limit=100';
        $projects = $callApi->getData($method, $apiKey, $url);

        $projectsList = json_decode($projects, true);



        return  $this->render('listeProjets.html.twig', [
            'projectsList' => $projectsList,

        ]);
    }

    /**
     *
     * @Route("client", name="client")
     */
    public function getSocByProj()
    {
        $idSoc = $_POST['idsoc'];
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];

        $db = new PDOConnection('mysql:host=localhost;dbname=dolibarr', 'vincent', 'root');
        $getSoc = $db->query('SELECT 
S.nom,
S.rowid,
S.address,
S.zip,
s.town,
P.ref,
P.title
FROM llx_societe as S
INNER JOIN llx_projet as P
ON S.rowid = P.fk_soc
WHERE S.rowid =' . $idSoc);

        $societe = $getSoc->fetch();

        $refAff = $societe['ref'];

        $template = $this->renderView('convocGR.html.twig', [
            'idSoc' => $idSoc,
            'societe' => $societe,
            'dateRdv' => $date,
            'heureRdv' => $heure

        ]);

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf8', 10);
//        $html2pdf->setTestIsImage(false);
        $html2pdf->setTestTdInOnePage(true);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocation'. $refAff .'.pdf');
    }



}