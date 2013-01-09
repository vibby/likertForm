<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Validator\Constraints as Assert;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
    ));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/view',
    'twig.options' => array('debug' => true)
    ));
    // $app['twig']->addExtension(new Twig_Extensions_Extension_Debug());

$config  = Yaml::parse(file_get_contents(__DIR__ . '/../config/config.yml'));

$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app['swiftmailer.options'] = $config['swiftmailer']['options'];

$app->match('/', function (Request $request) use ($app) {
    return $app['twig']->render('index.html.twig');
});

$app->match('/merci', function (Request $request) use ($app) {
    return $app['twig']->render('merci.html.twig');
});

$app->get('/login', function () use ($app) {
    $config  = Yaml::parse(file_get_contents(__DIR__ . '/../config/config.yml'));

    $username = $app['request']->server->get('PHP_AUTH_USER', false);
    $password = $app['request']->server->get('PHP_AUTH_PW');

    if ($config['user']['login'] === $username && $config['user']['password'] === $password) {
        $app['session']->set('user', array('username' => $username));
        return $app->redirect('/resultat');
    }

    $response = new Response();
    $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'Affichage des Resultats'));
    $response->setStatusCode(401, 'Connectez-vous');
    return $response;
});

$app->get('/resultat', function () use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $responses = array();
    if (($handle = fopen(__DIR__ . '/../reponses/_toutes.csv', "r")) !== FALSE) {
        while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $responses[] = $line;
        }
        fclose($handle);
    }

    $data  = Yaml::parse(file_get_contents(__DIR__ . '/../config/questions.yml'));
    // $likertScales = $data['likertScales'];
    $likertQuestions = $data['likertQuestions'];
    $questionsFlat = array();
    foreach ($likertQuestions as $page => $questions) {
        foreach ($questions as $question) {
            // $question['scale'] = $likertScales[$question['scale']];
            $questionsFlat[] = $question;
        }
    }
    $questionsFlat = array_merge($questionsFlat,array(
        array('label'=>'age'),
        array('label'=>'Sexe'),
        array('label'=>'Situation_famille'),
        array('label'=>'Nombre_enfants_a_charge'),
        array('label'=>'Profession'),
        array('label'=>'Secteur'),
        array('label'=>'Intitule_poste'),
        array('label'=>'Heures_travail_semaine'),
        array('label'=>'Heures_travail_semaine'),
        array('label'=>'Satisfaction_salaire'),
        array('label'=>'Duree_poste'),
        array('label'=>'Duree_entreprise'),
        array('label'=>'Societe'),
        array('label'=>'Domain'),
        array('label'=>'Domain_other'),
        array('label'=>'Nombre_salaries_etablissement'),
        array('label'=>'Nombre_salaries_entreprise'),
    ));

    $inversedResponses = array();
    foreach ($responses as $x => $line) {
        foreach ($line as $y => $item) {
            $inversedResponses[$y][$x] = $item;
        }
    }

    $iResponse = 0;
    $data = array();
    foreach ($questionsFlat as &$question) {
        if ("none" != $question['scale']) {
            if (array_key_exists($iResponse, $inversedResponses)) {
                $data[] = array_merge(array($question['label']),$inversedResponses[$iResponse]);
                $iResponse++;
            } else {
                $data[] = array($question['label']);
            }
        }
    }

    return $app['twig']->render('data.html.twig', array(
        'responses' => $data,
    ));
});

$app->match('/questionnaire', function (Request $request) use ($app) {

    $data  = Yaml::parse(file_get_contents(__DIR__ . '/../config/questions.yml'));
    $likertScales = $data['likertScales'];
    $likertQuestions = $data['likertQuestions'];

    $domains = array(
        'Industrie',
        'Agroalimentaire',
        );
    $sectors = array(
        'Public',
        'Privé',
        'Parapublic',
        );
    $jobs = array(
        "Agriculteurs exploitants",
        "Artisans",
        "Commerçants et assimilés",
        "Chefs d'entreprise de plus de 10 salariés ou plus",
        "Professions libérales et assimilés",
        "Cadres de la fonction publique, professions intellectuelles et artistiques",
        "Cadres d'entreprise",
        "Professions intermédiaires de l'enseignement, de la santé, de la fonction publique et assimilés",
        "Professions intermédiaires administratives et commerciales des entreprises",
        "Techniciens",
        "Contremaîtres, agents de maîtrise",
        "Employés de la fonction publique",
        "Employés administratifs d'entreprise",
        "Employés de commerce",
        "Personnels de services directs aux particuliers",
        "Ouvriers qualifiés",
        "Ouvriers non qualifiés",
        "Ouvriers agricoles"
        );
    $domains = array(
        "Agriculture",
        "Industrie",
        "Électricité, gaz et eau",
        "Construction",
        "Commerce",
        "Hôtels et restaurants",
        "Transport",
        "Communication",
        "Finances, banques et assurances",
        "Immobilier",
        "Administration publique",
        "Education - Enseignement",
        "Social - Aide aux personnes",
        "Santé",
        "Informatique et nouvelles technologies",
        "Autre, préciser ci-dessous",
        );

    $sessionData = $app['session']->get('data');
    if(!$sessionData) {
      $sessionData = array(time());
    }

    $idPage = 0;
    $found = false;
    do {
        $idPage++;
        if (!array_key_exists('page'. $idPage, $likertQuestions))
            $found = true;
        else foreach ($likertQuestions['page'. $idPage] as $qKey => $likertQuestion) {
            if (!array_key_exists('page'. $idPage.'_item'.$qKey, $sessionData)) {
                $found = true;
            }
        }
    } while (array_key_exists('page'. $idPage, $likertQuestions) && !$found);

    $formBuilder = $app['form.factory']->createBuilder('form', $sessionData);
    if ($idPage <= count($likertQuestions)) {
        foreach( $likertQuestions['page'. $idPage] as $qKey => $likertQuestion) {
            $constraint =
            null == is_array($likertQuestion['scale']) ?
            null :
            new Assert\Choice(array_keys($likertScales[ $likertQuestion['scale'] ])) ;
            $formBuilder->add( 'page'.$idPage.'_item'.$qKey , 'choice', array(
                'choices' => $likertScales[ $likertQuestion['scale'] ] ,
                'expanded' => true,
                'multiple' => false,
                'constraints' => $constraint,
                'attr' => array(
                    'class' => $likertQuestion['scale']. ' likert',
                    ),
                'label' => $likertQuestion['label'],
                ));
        }
    } else {
        $formBuilder
        ->add( 'age', 'integer', array(
            'label' => "Votre age",
            'required' => true,
            ))
        ->add( 'Sexe', 'choice', array(
            'choices' => array('Homme','Femme') ,
            'expanded' => true,
            'multiple' => false,
            'constraints' => new Assert\Choice(array(0,1)),
            'label' => "Sexe :",
            'required' => true,
            ))
        ->add( 'Situation_famille', 'choice', array(
            'choices' => array('Seul','En couple') ,
            'expanded' => true,
            'multiple' => false,
            'constraints' => new Assert\Choice(array(0,1)),
            'label' => "Situation familiale :",
            'required' => true,
            ))
        ->add( 'Nombre_enfants_a_charge', 'integer', array(
            'constraints' => new Assert\Type('Integer', 'Cette valeur doit être un nombre entier'),
            'label' => "Nombre d'enfants ou de personnes à votre charge :"
            ))
        ->add( 'Profession', 'choice', array(
            'choices' => $jobs ,
            'expanded' => false,
            'multiple' => false,
            'constraints' => new Assert\Choice(array_keys($jobs)),
            'label' => "Quelle est votre profession ?",
            'empty_value' => '',
            'required' => true,
            ))
        ->add( 'Secteur', 'choice', array(
            'choices' => $sectors ,
            'expanded' => false,
            'multiple' => false,
            'constraints' => new Assert\Choice(array_keys($sectors)),
            'label' => "Quel est le type de secteur de votre entreprise ?",
            'empty_value' => '',
            'required' => true,
            ))
        ->add( 'Intitule_poste', 'text', array(
            'label' => "Quel est l'intitulé exact de votre poste actuel ?",
            'required' => true,
            ))
        ->add( 'Heures_travail_semaine', 'integer', array(
            'label' => "Combien d'heures par semaine travaillez-vous ?",
            'required' => true,
            ))
        ->add( 'Heures_travail_semaine', 'integer', array(
            'label' => "Combien d'heures supplémentaires effectuez-vous par mois, environ ?",
            'required' => true,
            ))
        ->add( 'Satisfaction_salaire', 'text', array(
            'label' => "Êtes-vous satisfait(e) de votre salaire net mensuel ?",
            'required' => true,
            ))
        ->add( 'Duree_poste', 'text', array(
            'label' => "Depuis quand travaillez-vous dans votre poste actuel ?",
            'required' => true,
            ))
        ->add( 'Duree_entreprise', 'text', array(
            'label' => "Depuis quand travaillez-vous dans votre entreprise actuelle ?",
            'required' => true,
            ))
        ->add( 'Societe', 'text', array(
            'label' => "Nom de votre entreprise (facultatif) :",
            'required' => false ,
            ))
        ->add( 'Domain', 'choice', array(
            'choices' => $domains ,
            'expanded' => false,
            'multiple' => false,
            'constraints' => new Assert\Choice(array_keys($domains)),
            'label' => "A quelle branche appartient votre entreprise ?",
            'empty_value' => '',
            'required' => true,
            ))
        ->add( 'Domain_other', 'text', array(
            'label' => "Si autre, préciser",
            'required' => false ,
            ))
        ->add( 'Nombre_salaries_etablissement', 'integer', array(
            'label' => "Nombre de salariés dans votre  établissement (facultatif) :",
            'required' => false ,
            ))
        ->add( 'Nombre_salaries_entreprise', 'integer', array(
            'label' => "Nombre total de salariés dans votre entreprise (facultatif) :",
            'required' => false ,
            ))
        ;
    }
    $form = $formBuilder->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        if ($form->isValid()) {
            $formData = $form->getData();
            $data = array_merge($sessionData, $formData);
            $app['session']->set('data',$data);

            if ($idPage > count($likertQuestions)) {

                $time = time();
                $data = array_merge(array('dateFin' => $time),$data);

                $message = \Swift_Message::newInstance()
                    ->setSubject('[EnquêteVieAuTravail] Nouvelle réponse')
                    ->setFrom(array('noreply@univ-nantes.fr'))
                    ->setTo(array('vincent.beauvivre@gmail.com', 'kristina.beauvivre@gmail.com'))
                    ->setBody("Une nouvelle réponse au formulaire :\n\n".implode("\n",$data));

                $app['mailer']->send($message);

                $handle = fopen(__DIR__ . '/../reponses/_toutes.csv', 'w');
                fputcsv($handle, $data);

                $handle = fopen(__DIR__ . '/../reponses/'.$time.'.csv', 'w');
                fputcsv($handle, $data, chr(13));

                $app['session']->set('data',null);

              $nextPage = '/merci';
            } else {
              $nextPage = '/questionnaire';
            }

        return $app->redirect($nextPage);
        }
    }

            // display the form
    return $app['twig']->render('form.html.twig', array(
        'form' => $form->createView(),
        'shownPage' => $idPage,
        'scales' => array_keys($likertScales),
        'pages' => range(1, count($likertQuestions) + 1),
        ));
})->convert('idPage', function ($id) { return (int) $id; });

$app->run();
