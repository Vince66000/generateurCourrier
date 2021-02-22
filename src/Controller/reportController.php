<?php

namespace App\Controller;


use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use  \Spipu\Html2Pdf\Html2Pdf;



class reportController  extends  AbstractController {

//
//
//  const dbLocal = "aeb_dol";
//  const usrLocal = "aeb.dol";
//  const mdpLocal = 'DmPzTs61NzG4';


    const dbLocal = "dolibarr";
    const usrLocal = "vincent";
    const mdpLocal = 'root';




    /**
     * Affichage premiè_re page de l'appli
     * @return Response
     * @Route("/", name="index")
     */
    public function index() :Response
    {
        return  $this->render('home.html.twig');

    }

    /*****************************************************************************************************************************************************************
     *
     *                                                                      PARTIE CLIENT
     *
     * ***************************************************************************************************************************************************************
     */
    /**
     * Affiche le formulaire de convocs clients
     * @Route("report/client", name="report/client")
     */
    public function getFormCl() :Response
    {

        return  $this->render('report/formConvocClient.html.twig');
    }

    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("report/generateConvCl", name="report/generateConvCl")
     */
    public function getConvocClient() {

        $ref =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $dateReport = $_POST['datepicker2'];
        $heureReport = $_POST['timepicker2'];
        $expert = $_POST['expert'];
        $client = 'Client(e)';
        $demandeur = $_POST['annulation'];

        /**
         * requête qui récupère les coordonées d'un client lié à une affaire particulière
         */
        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db->prepare ('select 
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.fk_soc,
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
        $getSoc->bindParam(':ref', $ref);
        $getSoc->bindParam(':client', $client);

        $getSoc->execute();
        $societe = $getSoc->fetch();
        $refAff = $societe['ref'];
        $nomClient = $societe['lastname'];
//        $fk_soc = $societe['fk_soc'];
        $dateBrute = date('d-m-Y');

//        $getNom = $db->query('SELECT nom FROM llx_societe where rowid = ' . $fk_soc );
//        $nom = $getNom->fetch();

        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $societe['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 4; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }


        $template = $this->renderView('report/modelConvoc.html.twig', [

            'affaire' => $ref,
            'societe' => $societe,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'expert' => $expert,
            'lieuxexp' => $lieuxExp3,
            'dateReport' => $dateReport,
            'heureReport' => $heureReport,
            'demandeur' => $demandeur


        ]);

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocation_client_'. $nomClient .'_'. $refAff .'_' .$dateBrute .'.pdf');
    }




    /**
     * ******************************************************************************************************************************************************************************
     *                                     Partie Tiers
     * ******************************************************************************************************************************************************************************
     */
    /**
     * affiche le form qui demande le numÃ©rod'affaire
     * @return Response
     * @Route("report/convEse1", name="report/convEse1")
     */
    public function getProjEse() {

        return $this->render('report/FormConvEse1.html.twig');
    }


    /**
     * Récupère les contacts liés à l'affaire
     * @return Response
     * @throws \Doctrine\DBAL\Driver\PDOException
     * @Route("report/getProject" , name="report/getProject")
     */
    public function getProject() {

        $ref =  '%'. $_POST['refAffaire'] . '%' ;
        $client = 'Client(e)';
        $entreprise = 'Entreprise' . '%';
        $tiers = 'Tiers' . '%';


        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getProj = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                              and llx_c_type_contact.libelle IN  ("Entreprise 1", "Entreprise 2", "Entreprise 3", "Tiers 1", "Tiers 2", "Tiers 3", "Tiers 4", "Tiers 5", "Locataire",
                               "Expert 1", "Expert 2", "Expert 3", "Expert 4", "Expert 5", "Expert 6", "Expert 7", "Expert 8", "Expert 9")
                        ');

        $getProj->bindParam(':ref', $ref);
        $getProj->execute();

        $res = $getProj->fetchAll();

        return $this->render('report/formConvocEse.html.twig', [
            'res' => $res,
            'affaire' => $ref,

        ]);



    }

    /**
     * rÃ©cupÃ©ration des informations et GÃ©nÃ©ration de la convoc Ã  un tiers
     * @Route("report/ConvTiers", name="report/ConvTiers")
     */
    public function getConvocTiers()
    {

        $affaire = '%' . $_POST['affaire'] . '%';
        $nom = $_POST['nom'];
        $typeContact = $nom;
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
//        $dateDesordre = $_POST['dateDesordre'];
//        $natureTrav = $_POST['natureTrav'];
        $vref = $_POST['vref'];
//        $numReco = $_POST['numReco'];
        $interlocuteur = $_POST['interlocuteur'];
        $dossier = $_POST['dossier'];
        $pj =$_POST['pj'] ;
        $dateReport = $_POST['datepicker2'];
        $heureReport = $_POST['timepicker2'];
        $demandeur = $_POST['demandeur'];

        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getClient = $db->prepare(
            'SELECT
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.firstname,
                        llx_socpeople.lastname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        FROM llx_element_contact
                        INNER JOIN llx_projet
                        ON llx_element_contact.element_id = llx_projet.rowid  
                        INNER JOIN llx_projet_extrafields
                        ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        INNER JOIN llx_socpeople
                        ON llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact 
                        on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        WHERE llx_projet.ref LIKE  :affaire                      
                        AND  llx_c_type_contact.libelle  = :typeContact
                        ');

        $getClient->bindParam(':affaire', $affaire);
        $getClient->bindParam(':typeContact', $typeContact);
        $getClient->execute();
        $client = $getClient->fetch();

        $natContact = 'Client(e)';


        $getProj = $db->prepare('select   
                        llx_socpeople.civility as clientCiv,                 
                        llx_socpeople.lastname as clientPrenom, 
                        llx_socpeople.firstname as clientNom,
                        llx_socpeople.address as clientAdd,
                        llx_socpeople.zip as clientCP,
                        llx_socpeople.town as clientVille,
                        llx_socpeople.fk_soc,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = :client');

        $getProj->bindParam(':ref', $affaire);
        $getProj->bindParam(':client', $natContact);
        $getProj->execute();
        $res = $getProj->fetch();
        $id_soc = $client['fk_soc'];

        $getNameSoc = $db->prepare('SELECT nom as nomSoc FROM llx_societe where rowid = :fk_soc');
        $getNameSoc->bindParam(':fk_soc', $id_soc);
        $getNameSoc->execute();
        $nameSoc = $getNameSoc->fetch();

        if ($pj == 'oui') {

            $getPJ1 = $db->prepare('select            
                        llx_socpeople.lastname,  
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc,
                        llx_socpeople.town,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = "P.J"');

            $getPJ1->bindParam(':ref', $affaire);
            $getPJ1->execute();
            $PJ1 = $getPJ1->fetch();
            $fkSocPJ = $PJ1['fk_soc'];


            $getPJ2 = $db->prepare('SELECT nom as nomPJ FROM llx_societe where rowid = :fk_soc ');
            $getPJ2->bindParam('fk_soc', $fkSocPJ);
            $getPJ2->execute();
            $PJ2 = $getPJ2->fetch();

        }


        $lieuxExp = $client['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        $nomClient = $res['clientPrenom'];
        $dateCreation = date('d-m-y');


        for ($i = 4; $i < $count ; $i++) {
            $lieuxExp3 .= ' ' . $lieuxExp2[$i] . ' ';

        }
        if ($pj == 'oui') {

            $tempTiers = $this->renderView('report/modelConvocEse.html.twig', [
                'client' => $client,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
//                'dateDesordre' => $dateDesordre,
//                'natureTrav' => $natureTrav,
                'lieuxExp' => $lieuxExp3,
                'vref' => $vref,
                'res' => $res,
                'nameSoc' => $nameSoc,
//                'numReco' => $numReco,
                'pj' => $PJ2,
                'interlocuteur' => $interlocuteur,
                'dossier' => $dossier,
                'dateReport' => $dateReport,
                'heureReport' => $heureReport,
                'demandeur' => $demandeur

            ]);
        }
        else {
            $tempTiers = $this->renderView('report/modelConvocEse.html.twig', [
                'client' => $client,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
//                'dateDesordre' => $dateDesordre,
//                'natureTrav' => $natureTrav,
                'lieuxExp' => $lieuxExp3,
                'vref' => $vref,
                'res' => $res,
                'pj' => '',
                'nameSoc' => $nameSoc,
//                'numReco' => $numReco,
                'interlocuteur' => $interlocuteur,
                'dossier' => $dossier,
                'dateReport' => $dateReport,
                'heureReport' => $heureReport,
                'demandeur' => $demandeur

            ]);
        }

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);

        $html2pdf->writeHTML($tempTiers);
        $html2pdf->setTestTdInOnePage(true);
        return $html2pdf->output('convocation-tiers'. $nomClient . '_' . $affaire . '_' . $dateCreation . '.pdf');
    }

    /**
     * **********************************************************************************************************************************************************************************
     *                                                      Partie Assurance tiers
     * **********************************************************************************************************************************************************************************
     */

    /**
     * Affiche le formulaire de convocs clients
     * @Route("report/AssEse", name="report/AssEse")
     */
    public function getFormAssTiers() :Response
    {


        return  $this->render('report/formConvocAssTiers1.html.twig');
    }

    /**
     * @return Response
     * @Route("report/AssEse2", name="report/AssEse2")
     */
    public function getProject2()
    {

        $ref = '%' . $_POST['refAffaire'] . '%';
        $client = 'Client(e)';
        $entreprise = 'Entreprise' . '%';
        $assureur = 'Assureur' . '%';


        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getEse = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.rowid, 
                        llx_projet.ref,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle IN  ("Entreprise 1", "Entreprise 2", "Entreprise 3", "Tiers 1", "Tiers 2", "Tiers 3", "Tiers 4", "Tiers 5", "Locataire")

                        ');

        $getEse->bindParam(':ref', $ref);
//        $getEse->bindParam(':entreprise', $entreprise);
//        $getProj->bindParam(':client', $client);
        $getEse->execute();

        $Ese = $getEse->fetchAll();

        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getAss = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.rowid, 
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle IN  ("Assureur 1", "Assureur 2", "Assureur 3", "Courtier 1", "Courtier 2", "Courtier 3")
                        ');

        $getAss->bindParam(':ref', $ref);
//        $getAss->bindParam(':assureur', $assureur);
//        $getProj->bindParam(':client', $client);
        $getAss->execute();

        $Ass = $getAss->fetchAll();

        return $this->render('report/formConvocAssTiers.html.twig', [

            'ese' => $Ese,
            'ass' => $Ass,
            'affaire' => $ref

        ]);
    }
    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("report/ConvAss", name="report/ConvAss")
     */
    public function getConvocAssurance() {

        $ref2 =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
        $client = 'Client(e)';
        $assureur = $_POST['assurance'];
        $entreprise = $_POST['entreprise'];
        $vref = $_POST['vref'];
        $pj = $_POST['pj'];
        $interlocuteur = $_POST['interlocuteur'];
        $numDoss = $_POST['dossier'];
        $dateReport = $_POST['datepicker2'];
        $heureReport = $_POST['timepicker2'];
        $demandeur = $_POST['demandeur'];

        /**
         * requête qui récupère les coordonées d'un client lié à une affaire particulière
         */
        $db2 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db2->prepare ('select 
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
        $getSoc->bindParam(':ref', $ref2);
        $getSoc->bindParam(':client', $client);

        $getSoc->execute();
        $client = $getSoc->fetch();
        $refAff = $client['ref'];


        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getAss = $db->prepare ('select 
                        llx_projet.ref,
                        llx_socpeople.fk_soc,
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
                        and llx_c_type_contact.libelle like :assureur');
        $getAss->bindParam(':ref', $ref2);
        $getAss->bindParam(':assureur', $assureur);

        $getAss->execute();
        $assurance = $getAss->fetch();

        $fk_soc = $assurance['fk_soc'];


        $db3 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSocAss = $db3->prepare('SELECT nom from llx_societe where rowid = :fk_soc');

        $getSocAss->bindParam(':fk_soc', $fk_soc);

        $getSocAss->execute();
        $socAss = $getSocAss->fetch();

        $db4 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc2 = $db4->prepare('select 
                        llx_projet.ref,
                        llx_socpeople.fk_soc as fk_soc2,
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
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref = :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :entreprise ');
        $getSoc2->bindParam(':entreprise', $entreprise);
        $getSoc2->bindParam(':ref', $refAff);
        $getSoc2->execute();
        $Ese = $getSoc2->fetch();

        $fk_socEse = $Ese['fk_soc2'];

        $db3 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $ref3 = $Ese['ref'];
        $getEse = $db3->prepare('SELECT nom from llx_societe where rowid = :fk_soc');
        $getEse->bindParam(':fk_soc', $fk_socEse);

        $getEse->execute();
        $nomEse = $getEse->fetch();

        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $client['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 4; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }
        $nomClient = $client['lastname'];
        $dateCreation = date('d-m-y');

        if($pj == 'oui')
        {
            $getPJ1 = $db->prepare('select            
                        llx_socpeople.lastname,  
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc,
                        llx_socpeople.town,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = "P.J"');

            $getPJ1->bindParam(':ref', $ref2);
            $getPJ1->execute();
            $PJ1 = $getPJ1->fetch();
            $fkSocPJ = $PJ1['fk_soc'];

            $getPJ2 = $db->prepare('SELECT nom as nomPJ FROM llx_societe where rowid = :fk_soc ');
            $getPJ2->bindParam('fk_soc', $fkSocPJ);
            $getPJ2->execute();
            $PJ2 = $getPJ2->fetch();
        }


        if ($pj == 'oui')
        {
            $template = $this->renderView('report/modelConvocAssEse.html.twig', [


                'client' => $client,
                'assurance' => $assurance,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
                'lieuxexp' => $lieuxExp3,
                'socAss' => $socAss,
                'nomEse' => $nomEse,
                'vref' => $vref,
                'pj' => $PJ2,
                'interlocuteur' => $interlocuteur,
                'dossier' => $numDoss,
                'heureReport' => $heureReport,
                'dateReport' => $dateReport,
                'demandeur' => $demandeur

            ]);


        } else

            $template = $this->renderView('report/modelConvocAssEse.html.twig', [


                'client' => $client,
                'assurance' => $assurance,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
                'lieuxexp' => $lieuxExp3,
                'socAss' => $socAss,
                'nomEse' => $nomEse,
                'vref' => $vref,
                'pj' => '',
                'interlocuteur' => $interlocuteur,
                'dossier' => $numDoss,
                'heureReport' => $heureReport,
                'dateReport' => $dateReport,
                'demandeur' => $demandeur

            ]);

        {

        }
        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convation-assurance-tiers'.'_'. $nomClient . $refAff .'_'. $dateCreation.'.pdf');

    }



    /*****************************************************************************************************************************************************************
     *
     *   											PARTIE MULTIRISQUE CLIENT
     * ****************************************************************************************************************************************************************
     */



    /**
     * Affiche le formulaire de convocs clients
     * @Route("report/convocationAssClt", name="report/convocationAssClt")
     */
    public function getFormAssClient() :Response
    {

        return  $this->render('report/formConvocAssClient1.html.twig');
    }


    /**
     * @return Response
     * @Route("report/convocationAssClt2", name="report/convocationAssClt2")
     */
    public function geFormAssClt2()
    {

        $ref = '%' . $_POST['refAffaire'] . '%';
        $client = 'Client(e)';



        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getClt = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.rowid, 
                        llx_projet.ref,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :client
                        ');

        $getClt->bindParam(':ref', $ref);
//        $getEse->bindParam(':entreprise', $entreprise);
        $getClt->bindParam(':client', $client);
        $getClt->execute();

        $clt = $getClt->fetchAll();

        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getAss = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.rowid, 
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle IN  ("Assurance client")
                        ');

        $getAss->bindParam(':ref', $ref);
        $getAss->execute();

        $Ass = $getAss->fetchAll();

        return  $this->render('report/formConvocAssClient.html.twig', [


            'ass' => $Ass,
            'affaire' => $ref

        ]);
    }
    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("report/genConvAssClt", name="report/genConvAssClt")
     */
    public function getConvocMulti() {

        $libAss = $_POST['assurance'];
        $ref =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
        $client = 'Client(e)';
        $assureur = 'Assurance client';
        $vref = $_POST['vref'];
        $entreprise = 'Entreprise';
        $interlocuteur = $_POST['interlocuteur'];
        if(isset($_POST['pj']))
        {
            $pj = $_POST['pj'];
        }
        else
        {
            $pj = '';
        }
        $heureReport = $_POST['timepicker2'];
        $dateReport = $_POST['datepicker2'];
        $demandeur = $_POST['demandeur'];

        /**
         * requête qui récupère les coordonées d'un client lié à une affaire particulière
         */
        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db->prepare ('select 
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
        $getSoc->bindParam(':ref', $ref);
        $getSoc->bindParam(':client', $client);

        $getSoc->execute();
        $societe = $getSoc->fetch();

        $nomClient = $societe['lastname'];
        $refAff = $societe['ref'];


        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getAss = $db->prepare ('select 
                        llx_projet.ref as reffAff,
                        llx_socpeople.fk_soc as idSoc,
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
                        and llx_c_type_contact.libelle like :assureur');
        $getAss->bindParam(':ref', $refAff);
        $getAss->bindParam(':assureur', $libAss);

        $getAss->execute();
        $assurance = $getAss->fetch();

        $fk_soc = $assurance['idSoc'];
        $ref2 = $assurance['reffAff'];
        $dateCreation = date('d-m-Y');


        $db3 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSocAss = $db3->prepare('SELECT nom from llx_societe where rowid = :fk_soc');

        $getSocAss->bindParam(':fk_soc', $fk_soc);

        $getSocAss->execute();
        $socAss = $getSocAss->fetch();



        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $societe['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 4; $i < $count  ; $i++)
        {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }


        $template = $this->renderView('report/modelConvocAssClt.html.twig', [


            'societe' => $societe,
            'assurance' => $assurance,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'expert' => $expert,
            'lieuxexp' => $lieuxExp3,
            'socAss' => $socAss,
            'vref' => $vref,
            'interlocuteur' => $interlocuteur,
            'pj' => $pj,
            'heureReport' => $heureReport,
            'dateReport' => $dateReport,
            'demandeur' => $demandeur

        ]);

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('Convocation-multirisque '. $nomClient.'_'. $refAff.'_' .$dateCreation .'.pdf');
    }

    /**********************************************************************************************************************************************************************
     *
     *
     *                                                  Locataire non plus en cause
     *
     ***********************************************************************************/

    /**
     * Affiche le formulaire de convocs clients
     * @Route("report/Locataire", name="report/Locataire")
     */
    public function getFormLoc() :Response
    {

        return  $this->render('report/formConvocLoc.html.twig');
    }

    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("report/ConvLoc", name="report/ConvLoc")
     */
    public function getConvocLc() {

        $ref =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
        $locataire = 'Locataire';
        $dateReport = $_POST['datepicker2'];
        $heureReport = $_POST['timepicker2'];
        $demandeur = $_POST['demandeur'];

        /**
         * requête qui récupère les coordonées d'un client lié à une affaire particulière
         */
        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db->prepare ('select 
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
                        and llx_c_type_contact.libelle like :locataire');
        $getSoc->bindParam(':ref', $ref);
        $getSoc->bindParam(':locataire', $locataire);

        $getSoc->execute();
        $societe = $getSoc->fetch();

        $getClient = $db->prepare('SELECT nom FROM llx_societe inner join llx_projet on llx_societe.rowid = llx_projet.fk_soc where ref like :ref');
        $getClient->bindParam(':ref', $ref);
        $getClient->execute();
        $client = $getClient->fetch();
        $refAff = $societe['ref'];
        $nomClient = $client['nom'];
        $dateBrute = date('d-m-Y');

        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $societe['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 4; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }


        $template = $this->renderView('report/modelConvocLoc.html.twig', [

            'affaire' => $ref,
            'societe' => $societe,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'expert' => $expert,
            'lieuxexp' => $lieuxExp3,
            'dateReport' => $dateReport,
            'heureReport' => $heureReport,
            'demandeur' => $demandeur

        ]);

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocation_client_'. $nomClient .'_'. $refAff .'_' .$dateBrute .'.pdf');
    }



    /**
     * ******************************************************************************************************************************************************************************
     *                                    Report expert
     * ******************************************************************************************************************************************************************************
     */
    /**
     * affiche le form qui demande le numÃ©rod'affaire
     * @return Response
     * @Route("report/Expert1", name="report/Expert1")
     */
    public function getProjExp() {

        return $this->render('report/FormConvExpert1.html.twig');
    }


    /**
     * Récupère les contacts liés à l'affaire
     * @return Response
     * @throws \Doctrine\DBAL\Driver\PDOException
     * @Route("report/getProjExp" , name="report/getProjExp")
     */
    public function getProjExpert() {

        $ref =  '%'. $_POST['refAffaire'] . '%' ;
        $client = 'Client(e)';
        $entreprise = 'Entreprise' . '%';
        $tiers = 'Tiers' . '%';


        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getProj = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                              and llx_c_type_contact.libelle IN  ("Expert 1", "Expert 2", "Expert 3", "Expert 4", "Expert 5", "Expert 6", "Expert 7", "Expert 8", "Expert 9")
                        ');

        $getProj->bindParam(':ref', $ref);
        $getProj->execute();

        $res = $getProj->fetchAll();

        return $this->render('report/formConvocExpert.html.twig', [
            'res' => $res,
            'affaire' => $ref,

        ]);



    }

    /**
     * rÃ©cupÃ©ration des informations et GÃ©nÃ©ration de la convoc Ã  un tiers
     * @Route("report/ConvExp", name="report/ConvExp")
     */
    public function getConvocExp()
    {

        $affaire = '%' . $_POST['affaire'] . '%';
        $nom = $_POST['nom'];
        $typeContact = $nom;
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
//        $dateDesordre = $_POST['dateDesordre'];
//        $natureTrav = $_POST['natureTrav'];
        $vref = $_POST['vref'];
//        $numReco = $_POST['numReco'];
        $interlocuteur = $_POST['interlocuteur'];
        $dossier = $_POST['dossier'];
        $pj =$_POST['pj'] ;
        $dateReport = $_POST['datepicker2'];
        $heureReport = $_POST['timepicker2'];
        $demandeur = $_POST['demandeur'];

        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getClient = $db->prepare(
            'SELECT
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.firstname,
                        llx_socpeople.lastname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        FROM llx_element_contact
                        INNER JOIN llx_projet
                        ON llx_element_contact.element_id = llx_projet.rowid  
                        INNER JOIN llx_projet_extrafields
                        ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        INNER JOIN llx_socpeople
                        ON llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact 
                        on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        WHERE llx_projet.ref LIKE  :affaire                      
                        AND  llx_c_type_contact.libelle  = :typeContact
                        ');

        $getClient->bindParam(':affaire', $affaire);
        $getClient->bindParam(':typeContact', $typeContact);
        $getClient->execute();
        $client = $getClient->fetch();

        $natContact = 'Client(e)';


        $getProj = $db->prepare('select   
                        llx_socpeople.civility as clientCiv,                 
                        llx_socpeople.lastname as clientPrenom, 
                        llx_socpeople.firstname as clientNom,
                        llx_socpeople.address as clientAdd,
                        llx_socpeople.zip as clientCP,
                        llx_socpeople.town as clientVille,
                        llx_socpeople.fk_soc,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = :client');

        $getProj->bindParam(':ref', $affaire);
        $getProj->bindParam(':client', $natContact);
        $getProj->execute();
        $res = $getProj->fetch();
        $id_soc = $client['fk_soc'];

        $getNameSoc = $db->prepare('SELECT nom as nomSoc FROM llx_societe where rowid = :fk_soc');
        $getNameSoc->bindParam(':fk_soc', $id_soc);
        $getNameSoc->execute();
        $nameSoc = $getNameSoc->fetch();

        if ($pj == 'oui') {

            $getPJ1 = $db->prepare('select            
                        llx_socpeople.lastname,  
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc,
                        llx_socpeople.town,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = "P.J"');

            $getPJ1->bindParam(':ref', $affaire);
            $getPJ1->execute();
            $PJ1 = $getPJ1->fetch();
            $fkSocPJ = $PJ1['fk_soc'];


            $getPJ2 = $db->prepare('SELECT nom as nomPJ FROM llx_societe where rowid = :fk_soc ');
            $getPJ2->bindParam('fk_soc', $fkSocPJ);
            $getPJ2->execute();
            $PJ2 = $getPJ2->fetch();

        }


        $lieuxExp = $client['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        $nomClient = $res['clientPrenom'];
        $dateCreation = date('d-m-y');


        for ($i = 4; $i < $count ; $i++) {
            $lieuxExp3 .= ' ' . $lieuxExp2[$i] . ' ';

        }
        if ($pj == 'oui') {

            $tempTiers = $this->renderView('report/modelConvocExp.html.twig', [
                'client' => $client,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
//                'dateDesordre' => $dateDesordre,
//                'natureTrav' => $natureTrav,
                'lieuxExp' => $lieuxExp3,
                'vref' => $vref,
                'res' => $res,
                'nameSoc' => $nameSoc,
//                'numReco' => $numReco,
                'pj' => $PJ2,
                'interlocuteur' => $interlocuteur,
                'dossier' => $dossier,
                'dateReport' => $dateReport,
                'heureReport' => $heureReport,
                'demandeur' => $demandeur

            ]);
        }
        else {
            $tempTiers = $this->renderView('report/modelConvocEse.html.twig', [
                'client' => $client,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
//                'dateDesordre' => $dateDesordre,
//                'natureTrav' => $natureTrav,
                'lieuxExp' => $lieuxExp3,
                'vref' => $vref,
                'res' => $res,
                'pj' => '',
                'nameSoc' => $nameSoc,
//                'numReco' => $numReco,
                'interlocuteur' => $interlocuteur,
                'dossier' => $dossier,
                'dateReport' => $dateReport,
                'heureReport' => $heureReport,
                'demandeur' => $demandeur

            ]);
        }

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);

        $html2pdf->writeHTML($tempTiers);
        $html2pdf->setTestTdInOnePage(true);
        return $html2pdf->output('convocation-tiers'. $nomClient . '_' . $affaire . '_' . $dateCreation . '.pdf');
    }



}