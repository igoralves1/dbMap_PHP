dbMap_PHP
=========

Once we finished our database (MySQL) we need to create HTML objects to populate our database. This php class was developed to mapping 
a database then it creates classes (as many as your database tables). 
Inside of each of those previus classes, the code creates methods to gererate some HTML/HTML5 objects that will be in hand and will 
facilitate the code, when we are coding for a large project.The aim of this project is to make a kind of shortcut to prevent the boring code task.
Inside of each class, we can find general insert, delet and update methods that acts directlly over his tabes. 

<strong>Português</strong>:<br/>
Depois de criada a nossa base de dados relacional em MySQL, precisamos criar objetos HTML ou HTML5 para permitir a entrada de dados através do browser. Esta classe foi desenvolvida
para mapear a base de dados que será enviada como parâmetro do método <em><strong>myDbMap::Fn_dbMap</strong>(<span style="color:blue">$savePath</span>, $dsn, $username, $password, $db);</em>. Em seguida,
dinamicamente, este método irá criar classes e métodos que perimitirão a criação de objetos HTML ou HTML5 já preparados para BOOTSTRAP 3, jQuery com AJAX.
Esses métodos perimitirão a criação de objetos "input" ou "select" com pouca ou nenhuma modificação. Cada objeto do DOM possuirá um id único, no seguinte formato: id="tipo_nomeDaTabela_nomeDoCampo".
Se o desenvolvedor desejar modificar o id, isso poderá ser feito de maneira simples, bem como para a criação de classes e atributos dos elementos HTML/HTML5 do DOM.