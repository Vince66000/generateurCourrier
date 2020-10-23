<?php

namespace App\Controller;

use Doctrine\DBAL\Driver\PDOConnection;
use PhpOffice\PhpWord\PhpWord;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class courrierController extends AbstractController {


    //
//  const dbLocal = "aeb_dol";
//  const usrLocal = "aeb.dol";
//  const mdpLocal = 'DmPzTs61NzG4';

    const dbLocal = "dolibarr";
    const usrLocal = "vincent";
    const mdpLocal = 'root';


    /**
     *
     *
     *                      Courrier client pour assistance à réception
     *
     *
     */


    /**
     * @return string
     * @Route("CourrierClientAssRec", name="assistanceReception")
     */
    public function formCourrCltAssRep() :Response
    {

        return $this->render('courriers/formCourrCltAssRep.html.twig');
    }

    /**
     * @return string
     * @Route ("generateCourrAssRec", name="generateCourrAssRec")
     */
    public function generCourr()
    {

        $ref =  '%' .$_POST['affaire'] . '%';
        $expert = $_POST['expert'];
        $client = 'Client(e)';
        $entreprise = 'Entreprise';

        /**
         *  on récupère les coordonées du client
         */
        $db = new \PDO('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getClient = $db->prepare ('select
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.lastname,
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref )
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :client');
        $getClient->bindParam(':ref', $ref);
        $getClient->bindParam(':client', $client);

        $getClient->execute();
        $client = $getClient->fetch();

        /**
         *  .. puis celles de l'entreprise qui a fait les travaux
         */
        $getSoc = $db->prepare ('select
                        llx_socpeople.civility,
                        llx_socpeople.lastname,
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_socpeople.fk_soc,
                        llx_c_type_contact.libelle
                        from llx_element_contact
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref )
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :entreprise');
        $getSoc->bindParam(':ref', $ref);
        $getSoc->bindParam(':entreprise', $entreprise);
        $getSoc->execute();

        $societe = $getSoc->fetch();

        $fkSociete = $societe['fk_soc'];



        $GetnomSociete = $db->prepare('SELECT nom from llx_societe where rowid = :fk_soc');
        $GetnomSociete->bindParam(':fk_soc', $fkSociete);
        $GetnomSociete->execute();
        $nomSociete = $GetnomSociete->fetch();

        $numeroAffaire = $client['ref'];
        $numeroExpl = explode("-", $numeroAffaire);
        $initiale = $numeroExpl[2];

        $lieuxExp = $client['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 3; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }

        $template =  $this->renderView('courriers/modelCourrierCltAssRep.html.twig', [
            'societe' =>$societe,
            'client' => $client,
            'expert' => $expert,
            'lieuxexp' => utf8_encode($lieuxExp3),
            'nomSociete' => $nomSociete
        ]);


        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocationClt.pdf');


    }


    /******************************************************************************************************************************************
     *
     *                          courrier à PJ pour activation dossier
     *
     *
     *******************************************************************************************************************************************/


    /**
     * @return Response
     * @Route("formCourrAct1", name="formCourrAct1")
     */
    public function formCourrPjact1() {
        return $this->render('courriers/formCourrPjActivDoss.html.twig');
    }



    /**
     * @return string
     * @Route("getCourr", name="getCourr")
     */
   public function getCourr() :response
   {


       $ref3 =  '%' .$_POST['affaire'] . '%';
       $expert = $_POST['expert'];
       $client = 'Client(e)';
       $entreprise = 'Entreprise';
       $numReco = $_POST['numReco'];
       $vref = $_POST['vref'];
       $interlocuteur = $_POST['interlocuteur'];
       $natureTrav = $_POST['natureTrav'];
       $date = $_POST['datepicker'];
       $heure = $_POST['timepicker'];
       $check = $_POST['multi'];
       $eseConv = $_POST['Entreprise'];

       /**
        *  on récupère les coordonées du client
        */
       $db = new \PDO('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
       $getClient = $db->prepare ('select
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.lastname,
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref )
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :client');
       $getClient->bindParam(':ref', $ref3);
       $getClient->bindParam(':client', $client);

       $getClient->execute();
       $client = $getClient->fetch();

        $ref4 = $client['ref'];
       /**
        *  .. puis celles de l'entreprise qui a fait les travaux
        */
       $getSoc = $db->prepare ('select
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.lastname,
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_socpeople.fk_soc as idsoc,
                        llx_c_type_contact.libelle
                        from llx_element_contact
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref )
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :entreprise');
       $getSoc->bindParam(':ref', $ref4);
       $getSoc->bindParam(':entreprise', $entreprise);
       $getSoc->execute();

       $societe = $getSoc->fetch();

       $fkSociete = $societe['idsoc'];



       $GetnomSociete = $db->prepare('SELECT nom from llx_societe where rowid = :fk_soc');
       $GetnomSociete->bindParam(':fk_soc', $fkSociete);
       $GetnomSociete->execute();
       $nomSociete = $GetnomSociete->fetch();

       $numeroAffaire = $client['ref'];



       $getPJ = $db->prepare('select      
                        llx_socpeople.civility,      
                        llx_socpeople.lastname,  
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc as fk_ass,
                        llx_socpeople.town,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = "P.J"');

       $getPJ->bindParam(':ref', $numeroAffaire);
       $getPJ->execute();
       $PJ = $getPJ->fetch();
       $fkSocPJ = $PJ['fk_ass'];

       $GetnomAss = $db->prepare('SELECT nom from llx_societe where rowid = :fk_soc');
       $GetnomAss->bindParam(':fk_soc', $fkSocPJ);
       $GetnomAss->execute();
       $nomAss = $GetnomAss->fetch();


       $lieuxExp = $client['lieuaff'];
       $lieuxExp2 = explode(" ", $lieuxExp);
       $count = count($lieuxExp2);
       $lieuxExp3 = '';
       for($i = 3; $i < $count  ; $i++) {
           $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

       }

       $template =  $this->renderView('courriers/modelCourrPjActiv.html.twig', [
           'societe' =>$societe,
           'client' => $client,
           'expert' => $expert,
           'lieuxexp' => utf8_encode($lieuxExp3),
           'nomSociete' => $nomSociete,
           'nomAss' => $nomAss,
           'pj' => $PJ,
           'numReco' => $numReco,
           'vref' => $vref,
           'interlocuteur' => $interlocuteur,
           'textelibre' => $natureTrav,
           'dateRdv' => $date,
           'heureRdv' => $heure,
           'check' => $check,
           'eseConv' => $eseConv

       ]);


       $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
       $html2pdf->setTestTdInOnePage(false);
       $html2pdf->writeHTML($template);

       return $html2pdf->output('convocationClt.pdf');



   }

    /**
     * @return Response
     * @Route("formJudInter", name="formJudInter")
     */
   public function getFormJud() {

       return $this->render('courriers/formCourrEJ.html.twig');
   }


    /**
     * @return string
     * @throws \Spipu\Html2Pdf\Exception\Html2PdfException
     * @Route("modelJudInter", name="modelJudInter")
     */
   public function getModelJud() {


       $ref =  '%' .$_POST['affaire'] . '%';
       $expert = $_POST['expert'];
       $client = 'Client(e)';
       $date = $_POST['datepicker'];
       $numRef = $_POST['numRef'];
       $tgi = $_POST['tgi'];
       $expJud = 'Expert Judiciaire';
       $action = $_POST['action'];
       $debut = $_POST['debut'];


       /**
        *  on récupère les coordonées du client
        */
       $db = new \PDO('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
       $getClient = $db->prepare ('select
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.lastname,
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref )
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :client');
       $getClient->bindParam(':ref', $ref);
       $getClient->bindParam(':client', $client);

       $getClient->execute();
       $client = $getClient->fetch();


       $getExpert = $db->prepare ('select
                        llx_socpeople.civility,
                        llx_socpeople.lastname,
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_c_type_contact.libelle
                        from llx_element_contact
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref )
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :expert');

       $getExpert->bindParam(':ref', $ref);
       $getExpert->bindParam(':expert', $expJud);
       $getExpert->execute();
       $expertJud = $getExpert->fetch();

       $lieuxExp = $client['lieuaff'];
       $lieuxExp2 = explode(" ", $lieuxExp);
       $count = count($lieuxExp2);
       $lieuxExp3 = '';

       for($i = 3; $i < $count  ; $i++) {
           $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

       }


       $template =  $this->renderView('courriers/modelCourrEJInterv.html.twig', [

           'client' => $client,
           'expert' => $expert,
           'lieuxexp' => utf8_encode($lieuxExp3),
           'dateRef' => $date,
           'numref' => $numRef,
           'tgi' => $tgi,
           'expertJud' => $expertJud,
           'action' => $action,
           'debut' => $debut

       ]);


       $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
       $html2pdf->setTestTdInOnePage(false);
       $html2pdf->writeHTML($template);

       return $html2pdf->output('convocationClt.pdf');

   }

}