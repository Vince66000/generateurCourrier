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



class convocController  extends  AbstractController {


    /**
     * @return Response
     * @Route("/", name="index")
     */
    public function index() :Response
    {
        return  $this->render('home.html.twig');

    }

    /**
     * Affiche le formulaire de convocs clients
     * @Route("convocationClient", name="convocationClient")
     */
    public function getProjects() :Response
    {
//        $callApi = new callAPI();
//        $method = 'GET';
//        $apiKey = 'd07b51bb74af39ccbf3d59a0e8d833033dfa3b37' ;
//        $url = 'http://localhost/dolibarr/htdocs/api/index.php/projects?sortfield=t.rowid&sortorder=ASC&limit=100';
//        $projects = $callApi->getData($method, $apiKey, $url);

//        $projectsList = json_decode($projects, true);


        return  $this->render('formConvocClient.html.twig');
    }

    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("generateConvCl", name="generateConvCl")
     */
    public function getConvocClient()
    {
//        $dest = $_POST['destinataire'];
        $ref =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $civ = $_POST['civilite'];
        $expert = $_POST['expert'];
        $texteLibre = $_POST['textelibre'];
//        $entreprise = $_POST['entreprise'];


        $db = new PDOConnection('mysql:host=localhost;dbname=dolibarr', 'vincent', 'root');
        $getSoc = $db->prepare ('SELECT 
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
WHERE P.ref  LIKE :ref');
        $getSoc->bindParam(':ref', $ref);
        $getSoc->execute();
        $societe = $getSoc->fetch();

        $refAff = $societe['ref'];


        $template = $this->renderView('modelConvoc.html.twig', [

            'affaire' => $ref,
            'societe' => $societe,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'civ' => $civ,
            'expert' => $expert,
            'textelibre' => $texteLibre,
//            'entreprise' =>$entreprise

        ]);

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocation'. $refAff .'.pdf');
    }

    /**
     * @Route("convocationEse", name="convocationEse")
     */
    public function getConvocTiers() {

        $db = new PDOConnection('mysql:host=localhost;dbname=dolibarr', 'vincent', 'root');
        $getContact = $db->prepare(
            'SELECT 
                        llx_socpeople.firstname,
                        llx_socpeople.lastname
                        FROM llx_socpeople 
                        INNER JOIN llx_societe 
                        on llx_socpeople.fk_soc = llx_societe.rowid
                        INNER JOIN llx_projet
                        on llx_socpeople.fk_soc = llx_projet.fk_soc');

        $getContact->execute();

//        while($row = $getContact->fetchAll()) {
//            $nom = $row['firstname'];
//            $prenom = $row['lastname'];
//
//        }

        foreach ($getContact as $row) {
            $noms = $row['firstname'] . " " . $row['lastname'];

        }
    var_dump($noms);
    return $this->render('formConvocEse.html.twig', [
        'noms' => $noms,

    ]);

    }

    /**
     * @Route("generateConvEse", name="generateConvEse")
     */
//    public function getConvEse()
//    {
//
//
//    }

}