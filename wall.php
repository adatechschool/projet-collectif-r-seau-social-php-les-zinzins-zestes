<?php

include 'importBdd.php';
$mysqli = importBdd();

// Vérifier si un utilisateur est connecté
if (!isset($_SESSION['connected_id'])) {
    // Rediriger vers "login"
    header("Location: login.php");
    exit(); // Ici on sort après redirection (sécurité)
}

// On vérifie l'ID avec l'url
$wallId = isset($_GET['wall_id']) ? intval($_GET['wall_id']) : 0;

// Vérifie si l'ID est égal à 0
if ($wallId == 0) {
    // Comme pas de connexion, directement sur la page de login
    header("Location: login.php");
    exit(); // Idem plus haut
}

$connectedId = intval($_SESSION['connected_id']);

if ($wallId == $connectedId) {
    $userId = $connectedId;
    $myOwnWall = true;
} else {
    $userId = $wallId;
    $myOwnWall = false;
}


?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>ReSoC - Mur</title>
    <meta name="author" content="Julien Falconnet">
    <link rel="stylesheet" href="style.css" />
    <script>
        function deletePost(postId) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce post ?")) {
                window.location.href = "delete_post.php?post_id=" + postId;
            }
        }
    </script>
</head>

<body>
    <header>
        <?php include 'header.php' ?>
    </header>

    <?php

    $laQuestionEnSql = "SELECT 
        users.alias AS userAlias
        FROM users WHERE id= '$userId' ";
    $lesInformations = $mysqli->query($laQuestionEnSql);
    $user = $lesInformations->fetch_assoc();
    $userAlias = $user['userAlias'];

    //Cette requête sort un tableau des id des personnes suivies
    $ChercherLesGensQueJeSuis = "
            SELECT
            users.id AS followedId
            FROM followers 
            LEFT JOIN users ON users.id=followers.followed_user_id
            WHERE followers.following_user_id='$connectedId'
            GROUP BY users.id
            ";
    $leResultat = $mysqli->query($ChercherLesGensQueJeSuis);

    // Vérifie que l'utilisateur connecté est abonné à la personne dont est affiché le mur
    //Si la requête retourne qqch
    if ($leResultat) {
        //on initialise la variable isFollowing
        $isFollowing = false;
        //On parcourt les résultats de la requête (liste de mes abonnements) tant qu'il y a des résultats
        while ($follower = $leResultat->fetch_assoc()) {

            //si l'identité de la personne suivie est celle du mur où l'on se trouve, isFollowing devient true
            if (intval($follower['followedId']) === $wallId) {
                $isFollowing = true;
                break;
            }
        }

        //Si jamais la requête n'a rien retourné
    } else {
        echo "Échec de la requête : " . $mysqli->error;
    }


    if (!$myOwnWall) {
        include 'notMyWall.php';
        if (!$isFollowing) {
            include 'btnAbonne.php';
        } else {
            include 'btnDesabonne.php';
        }
    } else {
        include 'myWall.php';
    }

    ?>

    <div id="wrapper">

        <main>
            <?php

            $redirectionAdress = "Location: wall.php?wall_id=$userId";

            $chercherPostsDeCeMur = "
                    SELECT posts.content, posts.created, 
                    users.alias as author_name, 
                    users.id as author_id,
                    posts.id as postId,
                    COUNT(DISTINCT likes.id) as like_number, GROUP_CONCAT(DISTINCT tags.label) AS taglist 
                    FROM posts
                    JOIN users ON  users.id=posts.user_id
                    LEFT JOIN posts_tags ON posts.id = posts_tags.post_id  
                    LEFT JOIN tags       ON posts_tags.tag_id  = tags.id 
                    LEFT JOIN likes      ON likes.post_id  = posts.id 
                    WHERE posts.user_id='$userId' 
                    GROUP BY posts.id
                    ORDER BY posts.created DESC  
                    ";



            $lesPostsDeCeMur = $mysqli->query($chercherPostsDeCeMur);
            if (!$lesPostsDeCeMur) {
                echo ("Échec de la requete : " . $mysqli->error);
            }

            
            $tableauDeLikes = array();
            while ($post = $lesPostsDeCeMur->fetch_assoc()) {
                $postId = $post['postId'];

                // Requête SQL pour vérifier si l'utilisateur a liké ce post spécifique
                $compterNbDeLikes = "SELECT COUNT(*) AS like_count FROM likes WHERE user_id = $connectedId AND post_id = $postId"; // compte le nb de like sur un post donné
                $likeResult = $mysqli->query($compterNbDeLikes);
                $tableauAssociatifDeLikes = $likeResult->fetch_assoc();
                $isLikedPost = $tableauAssociatifDeLikes['like_count'] > 0;

                $tableauDeLikes[$postId] = $isLikedPost;
                $taglist = $post['taglist'];
            ?>

                <article>
                    <h3>
                        <time datetime='<?= $post['created'] ?>'>Le <?= date('d/m/Y', strtotime($post['created'])) ?> à <?= date('H:i:s', strtotime($post['created'])) ?></time>
                    </h3>
                    <address><a href="wall.php?wall_id=<?php echo $post['author_id'] ?>"><?php echo $post['author_name'] ?></a></address>
                    <div>
                        <p><?php echo $post['content'] ?></p>
                    </div>

                    <footer>
                        <?php
                        include 'footer.php';
                        ?>

                    </footer>

                </article>
            <?php 
        } ?>


        </main>
    </div>
</body>

</html>