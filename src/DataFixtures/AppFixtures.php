<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //Tableau de tableaux associatifs contenant les informations pour les attributs de chaque objet Product à instancier (name, description, price, stock, et l'instance de Category)
        $productArray = [
            ["name" => "Table Maecenas", 
            "description" => "Ceci est une Table, aenean gravida ante a placerat rhoncus. Mauris odio magna, aliquet id massa ut, convallis porttitor enim. Aenean sit amet leo eu nibh dictum scelerisque sit amet sed elit. Aliquam in hendrerit urna. Donec mollis commodo massa vel congue. Fusce ullamcorper nec diam non elementum. Nulla risus nulla, scelerisque sit amet est vel, sollicitudin suscipit erat. ", 
            "price" => 150, 
            "stock" => rand(1, 300),
            "category" => 'autre',
            ],
            ["name" => "Chaise Mauris", "description" => "Ceci est une Chaise, aliquam rhoncus lacus eget mattis. Cras sed porttitor mauris. Curabitur sit amet laoreet nisi, molestie interdum mi. Cras ullamcorper quam eu magna volutpat, eu consectetur lacus tincidunt. Cras vitae felis pretium mi blandit pellentesque vel quis sem. Sed scelerisque mi mauris, quis vehicula augue commodo non. Donec dui justo, convallis nec lacus congue, pretium faucibus mauris", "price" => 20, "stock" => rand(1, 300), "category" => 'chaise',],
            ["name" => "Armoire Etiam", "description" => "Ceci est une Armoire, a tempus diam, aliquam semper odio. Mauris sapien est, lacinia vitae sollicitudin euismod, vehicula feugiat leo. Aliquam nunc enim, feugiat at dolor et, hendrerit pharetra diam. Vivamus nec consectetur nunc. Donec dapibus turpis a laoreet ullamcorper. Donec eleifend arcu non sapien vehicula scelerisque nec at risus. Morbi non augue semper, luctus turpis vitae, tristique nunc. ", "price" => 500, "stock" => rand(1, 300), "category" => 'armoire',],
            ["name" => "Bureau Vestibulum", "description" => "Ceci est un Bureau, consequat rutrum aliquam. Morbi fringilla fringilla euismod. Nullam blandit tincidunt nulla, at porttitor dolor interdum nec.", "price" => 200, "stock" => rand(1, 300), "category" => 'bureau',],
            ["name" => "Lit Nulla", "description" => "Ceci est un Lit, vitae felis pretium mi blandit pellentesque vel quis sem. Sed scelerisque mi mauris, quis vehicula augue commodo non. Donec dui justo, convallis nec lacus congue, pretium faucibus mauris. Ut risus arcu, mattis non magna at, tincidunt egestas eros. Pellentesque sollicitudin, neque id euismod maximus, odio libero iaculis ligula, sed fringilla nulla nulla et magna. Quisque luctus ante orci. ", "price" =>400, "stock" => rand(1, 300), "category" => 'lit',],
            ["name" => "Table Craseget", "description" => "Ceci est une Table, vehicula feugiat leo. Aliquam nunc enim, feugiat at dolor et, hendrerit pharetra diam. Vivamus nec consectetur nunc. Donec dapibus turpis a laoreet ullamcorper. Donec eleifend arcu non sapien vehicula scelerisque nec at risus. Morbi non augue semper, luctus turpis vitae, tristique nunc.", "price" => 150, "stock" => rand(1, 300), "category" => 'autre',],
            ["name" => "Chaise Suspendisse", "description" => "Ceci est une Chaise,  pellentesque odio et velit luctus, vitae gravida sem sollicitudin. Proin ipsum dui, luctus at auctor at, rhoncus non elit. In nec nisl id odio pulvinar viverra at et mi. Integer ac suscipit ante, vitae ultrices dolor. Cras consequat rutrum aliquam. Morbi fringilla fringilla euismod.", "price" => 20, "stock" => rand(1, 300), "category" => 'chaise',],
            ["name" => "Armoire Pellentesque", "description" => "Ceci est une Armoire, ac velit eu quam imperdiet laoreet nec vel mi. Donec commodo orci eu nisi tincidunt facilisis nec quis sapien. Fusce lobortis turpis nec sem vulputate, interdum gravida magna porttitor. Sed lacus elit, feugiat et fermentum ac, pellentesque eget neque.", "price" => 500, "stock" => rand(1, 300), "category" => 'armoire',],
            ["name" => "Bureau Donec", "description" => "Ceci est un Bureau, in hendrerit urna. Donec mollis commodo massa vel congue. Fusce ullamcorper nec diam non elementum. Nulla risus nulla, scelerisque sit amet est vel, sollicitudin suscipit erat. ", "price" => 200, "stock" => rand(1, 300), "category" => 'bureau',],
            ["name" => "Lit", "description" => "Ceci est un Lit, pellentesque odio et velit luctus, vitae gravida sem sollicitudin. Proin ipsum dui, luctus at auctor at, rhoncus non elit. In nec nisl id odio pulvinar viverra at et mi. Integer ac suscipit ante, vitae ultrices dolor. Cras consequat rutrum aliquam. Morbi fringilla fringilla euismod. Nullam blandit tincidunt nulla, at porttitor dolor interdum nec. Donec dapibus, tortor at rhoncus vehicula, lorem ante ultricies mauris, auctor scelerisque ligula dui quis nisi. Quisque posuere eros sed sodales malesuada. ", "price" =>400, "stock" => rand(1, 300), "category" => 'lit',],
            ["name" => "Lit adulte 140x190 cm", "description" => "Créez une ambiance moderne et chaleureuse dans votre chambre grâce au lit adulte TEMPO 1 de coloris chêne nature de dimension 140x190cm. Ses lignes droites à l’esprit intemporel en feront une pièce de choix. Sa tête de lit impose un réel confort pour un sommeil de qualité. De plus, son épaisseur et la qualité de sa finition en font une pièce robuste et pérenne. De finition papier décor, il est fabriqué en France. Il existe aussi en coloris chêne relief, chêne argile, noyer brun et blanc brillant.", "price" => 119.99, "stock" => rand(1, 300), "category" => 'lit',],
            ["name" => "Lit 140x190", "description" => "Alliez confort, design et praticité avec le lit WILLIAM de 140x190 cm et ses multiples rangements. Son cadre à lattes inclus apporte un soutien qui promet des nuits confortables. Surtout, il réunit en un espace minimum de multiples rangements. Ses deux chevets intégrés munis de deux tiroirs coulissent pour venir se loger dans la structure du lit de chaque côté. D’autres espaces de rangement dont deux tiroirs de bonne hauteur de part et d’autre du lit sont disponibles. Ils permettent de loger sans mal une couette ou des oreillers supplémentaires. ", "price" => 339.80, "stock" => rand(1, 300), "category" => 'lit',],
            ["name" => "Lit adulte 140x190", "description" => " Créez une ambiance moderne et chaleureuse dans votre chambre grâce au lit adulte TEMPO 2 de coloris chêne argile de dimension 140x190cm. Ses lignes droites à l’esprit intemporel en feront une pièce de choix. Sa tête de lit impose un réel confort pour un sommeil de qualité. De plus, son épaisseur et la qualité de sa finition en font une pièce robuste et pérenne. De finition papier décor, il est fabriqué en France. Il existe aussi en coloris chêne nature, noyer brun, chêne relief et blanc brillant. ", "price" => 119.99, "stock" => rand(1, 300), "category" => 'lit',],
            ["name" => "Bureau 4 tiroirs", "description" => "Pratique : 4 tiroirs et 1 niche de rangement; Permet de travailler en toute sécurité avec tiroir à serrure; Existe aussi en d'autres coloris;", "price" => 99.99, "stock" => rand(1, 300), "category" => 'bureau',],
            ["name" => "Bureau 1 tiroir", "description" => "Le bureau ALPIN blanc donnera le ton à un intérieur contemporain épuré. De finition papier décor, son plateau mesure 100,8cm de long. Idéal pour y abriter vos documents, il possède un tiroir. Robuste et stable, il est fabriqué en France.", "price" => 39.99, "stock" => rand(1, 300), "category" => 'bureau',],
            ["name" => "Bureau 123 cm", "description" => "Compact et fonctionnel, ce bureau a été conçu pour ceux qui aiment voir chaque chose à sa place! Grâce à sa partie supérieure dotée de niches et d'étagères et son caisson pourvu d'un tiroir et d'un rangement à porte battante, il vous permettra de ranger bien distinctement tout votre matériel. Malin, son sur-meuble est paré de trois élastiques pour retenir vos mémos ou vos courriers en cours de traitement! A la pointe de la technologie, il est muni d'un port USB et d'un passe-câbles hyper-pratique. Son revêtement à l'aspect chêne et blanc apportera de la clarté à votre espace de travail.", "price" => 161.00, "stock" => rand(1, 300), "category" => 'bureau',],
            ["name" => "Chaise HAWAI anthracite", "description" => "Existe en deux coloris; Inspiration industrielle; Pieds en métal;", "price" => 79.48, "stock" => rand(1, 300), "category" => 'chaise',],
            ["name" => "Table 180cm allonge","description" => "Finition laquée brillante; Design avec ses glaces argentées sérigraphiées; Fabriqué en France;", "price" => 499.55, "stock" => rand(1, 300), "category" => 'autre',],
            ["name" => "Canapé d'angle réversible", "description" => "Dimanche en famille ou soirée entre amis? Ce canapé d'angle réversible quatre places en tissu gris va devenir la pièce maîtresse de votre intérieur pour tous vos moments de convivialité et de détente. Son garnissage en mousse généreux et ses coussins moelleux assurent une assise enveloppante et idéale. Vos invités restent pour la nuit? Pas de problème, il se déplie en un seul geste en un couchage spacieux et ultra-confortable pour deux personnes. Pratique, il est doté d'un coffre pour y ranger vos couettes et coussins. Sa structure en panneau de particules et ses pieds en bois promettent une solidité et une stabilité durables.", "price" => 479.00, "stock" => rand(1, 300), "category" => 'canape',],
            ["name" => "Canapé d'angle droit", "description" => "Têtières ajustables; Bimatière; Existe en version convertible;", "price" => 987.40, "stock" => rand(1, 300), "category" => 'canape',],
            ["name" => "Canapé d'angle convertible", "description" => "Sobre et élégant, le canapé d'angle convertible et réversible ROMY apportera de la personnalité à votre séjour. Son revêtement en polyuréthane est un gage de qualité et facile à entretenir. Pratique pour sa modularité (angle gauche ou droit), le canapé 4 places ROMY se transforme également en lit en toute simplicité et assura de bonnes nuits de sommeil à vos invités.", "price" => 529.38, "stock" => rand(1, 300), "category" => 'canape',],
            ["name" => "Canapé d'angle tolbiac", "description" => "Le canapé TOLBIAC est un magnifique canapé d'angle au revêtement en simili très confortable. Un canapé d'angle avec un effet lounge bar qui invite au repos et à la détente dans votre salon. Ce grand canapé familial sera idéal dans tous les intérieurs contemporains avec son espace de rangement ultra pratique caché sous la méridienne. Ses pieds en métal apportent encore plus de modernité à son look déjà très contemporain. Quand vous êtes assis sur ce canapé, la méridienne se situe sur votre gauche.", "price" => 599.00, "stock" => rand(1, 300), "category" => 'canape',],
        ];

        //La liste de nos différentes catégories sous forme d'un tableau associatif, contenant une indication du type de catégorie sous la clef et l'objet Category en valeur. Etant donné que nous allons instancier les Category plus tard dans une boucle, la valeur actuelle de ces différentes clefs est null.
        $categoryArray = ['autre' => null, 'chaise' => null, 'bureau' => null, 'canape' => null, 'lit' => null, 'armoire' => null];

        $lorem = "Pellentesque lobortis sodales nisl a ultricies. Praesent viverra ultricies dolor. Sed iaculis urna erat. Cras lectus eros, ornare sit amet euismod et, tincidunt quis risus. Sed ut sollicitudin risus. Pellentesque hendrerit nisl quis orci cursus fermentum. Vivamus ornare pellentesque neque eleifend vehicula. Suspendisse volutpat lectus vel sapien imperdiet, vel suscipit tellus facilisis.
        
        Vestibulum lacus turpis, rhoncus eu arcu id, euismod gravida nisi. Integer rhoncus, lacus vitae varius dignissim, arcu neque rutrum diam, at varius nisi nunc quis arcu. Quisque elementum nulla et urna placerat, a pharetra risus suscipit. Sed libero felis, aliquet nec hendrerit eget, sagittis ac massa. Mauris mattis lectus et elit faucibus, dignissim dapibus lacus pulvinar. Quisque venenatis lacus vitae felis pharetra, ac dictum dui feugiat. Morbi ac justo nisl. Sed sit amet efficitur ligula.";

        //Renseignement et implémentation de la liste des Category
        foreach($categoryArray as $key => &$value){ //Nous récupérons le tableau des Category à implémenter
            //Le & avant $value est une référence, ce qui signifie que nous récupérons la variable en tant que telle plutôt que sa valeur, ce qui nous permet de modifier notre tableau $categoryArray plutôt qu'une copie de $value, abandonnée après la boucle
            $value = new Category; //A chaque valeur est attribuée un nouveau objet Category
            $value->setName(ucfirst($key)); //Le nom est la clef capitalisée de l'index
            $value->setDescription($lorem); //La description est juste lorem ipsum
            $manager->persist($value); //Demande de persistance
        }

        //Utiliser un foreach sur le tableau NON-associatif $productArray fera boucler le tableau autant de fois qu'il possède d'entrées (si non modifié, 22 fois)
        foreach($productArray as $productData){
            //ProductData car chaque entrée (tableau associatif) contient les informations (data) avec lesquelles nous allons renseigner chacun de nos Products à instancier
            //Nous créons un nouveau Product:
            $product = new Product;
            //Nous renseignons notre Product avec les informations de $productData
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setStock($productData['stock']);
            $product->setCategory($categoryArray[$productData['category']]); //Nous récupérons l'objet de $categoryArray tenu par la clef dont le nom est fourni par la valeur 'category' de $productData
            //Une fois que nos attributs sont renseignés, nous persistons notre $product
            $manager->persist($product);
        }

        //Création de tags
        $tags = ['Neuf', 'Pas cher', 'Occasion', 'Promotion', 'Bois', 'Limité', 'Nouveau', 'Deluxe', 'Made in France'];
        $tagList = [];
        foreach($tags as $tagName){
            //Nous créons un Tag par entrée de notre tableau
            $tag = new \App\Entity\Tag;
            $tag->setName($tagName);
            array_push($tagList, $tag);
            $manager->persist($tag);
        }

        //Nouveaux Produits
        //On crée une liste de catégories potentielles
        $categories = ['armoire', 'chaise', 'bureau', 'canape', 'lit', 'autre'];
        for($i=0;$i<158;$i++){
            //On sélectionne un nom de catégorie au hasard qui servira à nommer le Product et à déterminer la clef que nous utiliserons dans $categoryArray
            $selectedCategory = $categories[rand(0, (count($categories) -1))];
            $product = new Product; //On crée un nouveau produit
            $product->setName(ucfirst($selectedCategory) . '#' . rand(1000,9999));
            $product->setDescription($lorem);
            $product->setPrice(rand(5,200) + 0.99);
            $product->setStock(rand(1,300));
            foreach($tagList as $tag){
                //Si la valeur choisie par rand() de 0 à 10 est supérieure à 8, nous lions le tag actuellement lu
                if(rand(0,10) > 8){
                    $product->addTag($tag);
                }
            }
            $product->setCategory($categoryArray[$selectedCategory]); //Nous récupérons l'objet de categoryArray tenu par la clef dont le nom est fourni par la valeur $selectedCategory choisie au hasard
            $manager->persist($product);
        }

        $manager->flush();
        //Nous appliquons notre méthode load grâce à la commande suivante
        //  php bin/console doctrine:fixtures:load
    }
}
