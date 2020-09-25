<?php

namespace App\Controller;


use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use  \Spipu\Html2Pdf\Html2Pdf;



class convocControllerCivis  extends  AbstractController {


//
//  const dbLocal = "aeb_dol";
//  const usrLocal = "aeb.dol";
//  const mdpLocal = 'DmPzTs61NzG4';

    const dbLocal = "dolibarr";
    const usrLocal = "vincent";
    const mdpLocal = 'root';

    /********************************************************************************************************************************************************************************
     *                                                     Partie Client  civis
     * ******************************************************************************************************************************************************************************
     */
    /**
     * Affiche le formulaire de convocs clients civis
     * @Route("convocationClientCiv", name="convocationClientCiv")
     */
    public function getFormClCiv() :Response
    {

        return  $this->render('formConvocClientCivis.html.twig');
    }

    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("generateConvClCiv", name="generateConvClCiv")
     */
    public function getConvocClientCiv() {

        $ref =  '%' .$_POST['affaire'] . '%';
        $nom = $_POST['nomSoc'];
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $civ = $_POST['civilite'];
        $expert = $_POST['expert'];
        $texteLibre = $_POST['textelibre'];

        /**
         * requête qui récupère les coordonées d'une affaire particulière
         */
        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db->prepare ('SELECT
P.ref,
PE.lieuaff,
P.title
FROM llx_projet as P
INNER JOIN llx_societe as S
ON  P.fk_soc = S.rowid 
INNER JOIN llx_projet_extrafields as PE
on P.rowid = PE.fk_object
WHERE P.ref  like :ref');
        $getSoc->bindParam(':ref', $ref);
        $getSoc->execute();
        $societe = $getSoc->fetch();


        /**
         * requête qui récupère les coordonées du client
         */
        $db2 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getClient = $db2->prepare ('SELECT
S.nom,
S.rowid,
S.address,
S.zip,
S.town
FROM llx_societe as S
WHERE S.nom  = :nom ');
        $getClient->bindParam(':nom', $nom);
        $getClient->execute();
        $client = $getClient->fetch();

        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $societe['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 3; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }
        $refAff = $societe['ref'];

        $template = $this->renderView('modelConvocClCivis.html.twig', [

            'affaire' => $ref,
            'societe' => $societe,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'civ' => $civ,
            'expert' => $expert,
            'textelibre' => $texteLibre,
            'lieuxexp' => utf8_encode($lieuxExp3),
            'client' => $client

        ]);

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocationClt'. $refAff .'.pdf');
    }




    /**
     * **********************************************************************************************************************************************************************************
     *                                                      Partie Entreprise Civis
     * **********************************************************************************************************************************************************************************
     */

    public function getProjEse() {

        return $this->render('FormConvEse1.html.twig');
    }


    /**
     *
     * @return Response
     * @throws \Doctrine\DBAL\Driver\PDOException
     * @Route("getProject" , name="getProject")
     */
    public function getProject() {

        $ref =  '%'. $_POST['refAffaire'] . '%' ;

        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getProj = $db->prepare('select ref, lastname, firstname, poste
from llx_element_contact 
inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref )');
        $getProj->bindParam(':ref', $ref);
        $getProj->execute();

        $res = $getProj->fetchAll();


        return $this->render('formConvocEse.html.twig', [
            'res' => $res,
            'affaire' => $ref,
        ]);



    }

    /**
     * récupération des informations et Génération de la convoc à un tiers
     * @Route("generateConvTiers", name="generateConvTiers")
     */
    public function getConvocTiers() {

        $affaire =  '%'. $_POST['affaire'] . '%';
        $nom =  $_POST['nom'] ;
        $noms = explode(" ", $nom);
        $prenom = $noms[0];
        $nomFamille = $noms[1];
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
        $dateDesordre = $_POST['dateDesordre'];
        $natureTrav = $_POST['natureTrav'];
        $vref = $_POST['vref'];

        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getClient = $db->prepare(
            'SELECT
                        llx_projet.title,
                        llx_projet.ref,
                        llx_societe.nom as nomClient,
                        llx_societe.address as addclient,
                        llx_societe.zip as cpclient,
                        llx_societe.town as villeclient,
                        llx_projet_extrafields.lieuaff
                        FROM llx_projet
                        INNER JOIN llx_societe
                        on llx_projet.fk_soc = llx_societe.rowid
                        INNER JOIN llx_projet_extrafields
                        ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        WHERE llx_projet.ref LIKE  :affaire');

        $getClient->bindParam(':affaire', $affaire);
        $getClient->execute();
        $client = $getClient->fetch();

        $db2 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getContact = $db2->prepare(
            'SELECT
                        llx_socpeople.civility,
                        llx_socpeople.lastname,
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town
                        FROM llx_element_contact
                        inner join llx_socpeople 
                        on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :affaire )');

        $getContact->bindParam(':affaire', $affaire);

        $getContact->execute();
        $contact = $getContact->fetch();

        $lieuxExp = $client['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';

        for($i = 3; $i < ($count - 1) ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }

        $tempTiers = $this->renderView('modelConvocEse.html.twig', [
            'contact' => $contact,
            'client' => $client,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'expert' => $expert,
            'dateDesordre' => $dateDesordre,
            'natureTrav' => $natureTrav,
            'lieuxExp' => utf8_encode($lieuxExp3),
            'vref' => $vref

        ]);
        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf8', 10);

        $html2pdf->writeHTML($tempTiers);
        $html2pdf->setTestTdInOnePage(true);
        return $html2pdf->output('convocationTiers'. $affaire .'.pdf');

    }



}