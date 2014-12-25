<?php 
class dbBase {


static $dsn ="localhost";
private static $username ="root";
private static $password ="";
private static $db ="dbcasstest";
private static $dbEstrut ='{"USER_T":["PK_USER","NAME","LASTNAME"]}';



//Conecta com a base de dados e retorna o link de conexão em caso de sucesso.
static function dbConnect() {
            $link = mysql_connect(self::$dsn, self::$username, self::$password);
            if (!$link) {
                    echo "No SERVER connection ==> " . mysql_error();
            } 
            else {
                    if (!mysql_select_db(self::$db)) {
                            echo "No DB connetion ==> " . mysql_error();
                    } 
                    else {
                            return $link;
                    }
            }
    }
static function dbClose($link) {
        mysql_close($link);
}
/**
Embora a variável private static $dbEstrut, seja inicializada com um objeto JSON do mapa da base de dados,
Essa função é imoprtante para garantir a atualização do mapeamento mesmo depois que a classe foi criada e a 
 base de dados modificada.
*/
static function bdEstrutura() {
    return json_decode(self::$dbEstrut,TRUE);
}
/**
* 
* @param type $tabName
* @param type $selectFields
* @param type $complemento
* @param type $arrUnidimensional
* @param type $asIndex 
* @return type
* 
* $tabName -> Pode ser uma STRING, um único nome de tabela, com ou sem aspas ou single quote, por exemplo $tabName="tab_name", $tabName=tab_name, $tabName='tab_name'.
* Pode ser também uma array de nome de tabelas envolvidas na query, por exemplo $tabName=array("tab1_name","tab2_name","tab3_name"), tambem com ou sem aspas ou single quote. Pode ser também uma array de statements (frases) pertinentes a clausula SELECT, por exelpo $tabName=array("tab1_name AS tb1","tab2_name AS tb2","tab3_name AS tb3").
* 
* $selectFields -> Pode ser uma STRING, um único nome de campos, com ou sem aspas ou single quote, por exemplo $selectFields="field1" ou $selectFields="field1,field2". Aspas ou sigle quote obrigatório, se o número de campos for maior do que 1.  
* 
* $complemento -> Uma STRING represnetando o complemento da frase select, exemplo $complemento="WHERE field1=field3 ORDER BY field1"
* 
* $arrUnidimensional - > A função sempre retornará uma array multidimensional se houver mais do que um registro com reposta da query. Porém se a resposta for somente um registro, haverá a opção de retornar uma array MULTIDIMENSIONAL OU UNIDIMENSIONAL. Será unidemensional par default. Se for desejada uma array nidimensional, o valor  de $arrUnidimensional deve ser FALSE. Exemplo $arrUnidimensional=FALSE
* 
* $asIndex -> Seleciona qual o nome do campo que vai servir como index da array, baseado nos campos da tabela da consulta sql, onde o primeiro campo tem index ZERO.. Nota: se for esperado mais de uma linha no resultado, o valor escolhico como index não pode se repetir. Se for escolher um valor que se repete, terá que alterar o código, tirando os comentários. 
* 
*/
static function MySql_Select($tabName, $selectFields=NULL,$complemento=NULL,$arrMultidimensional=TRUE,$asIndex=NULL) {
        $link = self::dbConnect();
        
        if(gettype($tabName)==="array"){
            //Cria a frase das tabelas que serão utilizadas no select statement
            foreach ($tabName as $value) {
                $tabs .= " " . $value . ",";
            }
            //Retira a ultima virgula
            $tabs = trim($tabs,",");
            $tabStatement = $tabs;
            #$sql = "SELECT * FROM $tabs $complemento";
            
        }
        else{
            $tabStatement = $tabName;
        }
        
                
        if(gettype($selectFields)==="array"){
            if(count($selectFields)>0){
                //Cria a frase dos campos que serão selecionados no select
                foreach ($selectFields as $value) {
                    $fields .= " " . $value . ",";
                }
                //Retira a ultima virgula
                $fields = trim($fields,",");  
                $fieldStatement=$fields;
            }
        }
        else{
            if($selectFields===NULL || $selectFields===""){
                $fieldStatement="*";
            }
            else{
                $fieldStatement=$selectFields;
            }
        }
        
        $sql = "SELECT $fieldStatement FROM $tabStatement $complemento";   
        $result = mysql_query($sql);
        //$resourceType = get_resource_type($result);
        
        if (!$result) {//Retorna o typo de erro ocorrido
            $resultadoErro = mysql_error();
            
            self::dbClose($link);
            return $resultadoErro;
        } 
        else {
                $nbRow = mysql_num_rows($result);
                if ($nbRow === 0) {
                    $arrayTot=array();
                } 
                elseif ($nbRow === 1) {//Se o resultado for 1 linha, existe a opção de reornar uma array multidimensional ou uma array UNIdimensional
                        if($arrMultidimensional){       
                                while ($row = mysql_fetch_assoc($result)) {     
                                    //$arrayTot[] = $row;
                                    if(!is_null($asIndex)){
                                     $arrayTot[$row[$asIndex]] = $row;
                                    }
                                    else{
                                        $arrayTot[] = $row;
                                    }
                                }
                        }
                        else{
                                while ($row = mysql_fetch_assoc($result,MYSQL_ASSOC)) {
                                    $arrayTot = $row;
                                }
                        }
                } 
                elseif ($nbRow > 1) {//Se o resultado for maior do que 1 elemnto a array só poderá ser MULTIdimensional
                    $arrI=NULL;
                   while ($row = mysql_fetch_assoc($result)) {
                         //$arrayTot[] = $row;
                         if(!is_null($asIndex)){
                          $arrayTot[$row[$asIndex]] = $row;
                          //$arrId=$row[$asIndex];
                         }
                         else{
                             $arrayTot[] = $row;
                         }
                    }
//                    if(!is_null($arrId)){
//                     $arrIntern[$arrId]=$arrayTot;
//                     $arrayTot=array();
//                     $arrayTot=$arrIntern;
//                    }
                }//Fim do elseif

                self::dbClose($link);
                return $arrayTot;
        }
    }

/**
* 
* @param string $tabName
* @param array $valores
* @param array $dbEstrutura 
* @return type
* 
* Se a array da $dbEstrutura não for fornecida então a função irá fazer uma consulta na base dados para procurar sua estrutura.
* Isso permite economizar tempo, pois se já possuimos a array estrura para uma sequencia de inserts nao será feita 
* uma consulta na base de dados a cada insert 
*/
static function Insert_Geral($tabName, array $valores, $dbEstrutura = NULL,$lastId=TRUE,$strErro=TRUE) {
        //Estrutura do Bd em forma de array        
        if ($dbEstrutura === NULL) {
         $dbEst = self::bdEstrutura();
        } else {
         $dbEst = $dbEstrutura;
        }

        //Forma a Estring dos campos a serem inseridos valores
        foreach ($dbEst[$tabName] as $value) {
         $fields .= $value . ",";
        }
        //Retira a ultima virgula
        $fields = trim($fields, ",");
        //Forma a string dos valores a serem inseridos
        foreach ($valores as $value) {
             if ($value == NULL) {
                 $value = "NULL";
             } 
             elseif (is_string($value)) {
                 $value = "\"$value\"";
             } 
             else {
                 $value = $value;
             }
             $strVal.=$value . ",";
        }
        //Retira a ultima virgula
        $strVal = trim($strVal, ",");
        $link = self::dbConnect();
        $sql = "INSERT INTO  $tabName ( $fields ) VALUES ( $strVal )";
        mysql_query($sql);
        
        //Returns the number of affected rows on success, and -1 if the last query failed.
        $resultado = mysql_affected_rows();
        
        if ($resultado > 0) {
               $LId = mysql_insert_id();
               self::dbClose($link);
               if($lastId){//Se foi requisitado 
                   return $LId;
               }
               else{
                   return $resultado;
               }
        } 
        else {
                if($strErro){
                     $erro = mysql_error();
                     self::dbClose($link);
                     return $erro;
                }
                else{
                      self::dbClose($link);
                      return $resultado;
                }
        }
 }
static function UpdateGeral($tabName,array $fieldVal, $complemento=NULL) {
       

     //If you omit the WHERE clause, all records will be updated!
     
     foreach ($fieldVal as $key => $value) {      
          if(is_string($value)){
           $fiedVal.="$key=\"$value\",";
          }
          else{
           $fiedVal.="$key=$value,";
          }
     }        
     $strVal = trim($fiedVal,",");
        $link = self::dbConnect();
        $sql = "UPDATE  $tabName SET  $strVal  $complemento ";   
        mysql_query($sql);
        //Returns the number of affected rows on success, and -1 if the last query failed.
        $resultado = mysql_affected_rows();
        if($resultado>0){            
            self::dbClose($link);
            return $resultado;
        }
        else{
            $erro=mysql_error();
            self::dbClose($link);
            return $erro;
        }
    }
static function DeleteGeral($tabName, $complemento=NULL) {
                
        $link = self::dbConnect();
        $sql = "DELETE FROM  $tabName $complemento";   
        mysql_query($sql);
        
        ///* this should return the correct numbers of deleted records */
        $resultado = mysql_affected_rows();
        if($resultado>0){            
            self::dbClose($link);
            return $resultado;
        }
        else{
            $erro=mysql_error();
            self::dbClose($link);
            return $erro;
        }
    }
static function HTML_Input(array $input_Attr) {
        $attrInput = null;
        foreach ($input_Attr as $key => $value) {
            if (is_string($key)) {
                $attrInput .= " " . $key . "=\"" . $value . "\"";
            } else {
                $Label = $value;
            }
        }
        //Geraçao do objeto <input> personalizado.
        return "<input " . $attrInput . "/>" . $Label;
    }
static function HTML_Select(array $conteudoA, $selected = NULL,$disabled=NULL, $attrSelectA = NULL, $multiple=NULL,$classMarkerCSS=NULL) {
        //Só cria o cboBox se na array $conteudoA houver ao menos 1 elemento
        if (count($conteudoA) > 0) {
            $disableControl=1;
            //Cria a frase de atributuos de select
            $attrSelect = null;
            
            //Geracao dos atributos da tag <select>
            if(gettype($attrSelectA)==="array" && count($attrSelectA)){
                foreach ($attrSelectA as $key => $value) {
                    $attrSelect .= " " . $key . "=\"" . $value . "\"";
                }
            }
            
            //echo HTMLCLASS::HTML_Select3($arr)."<br>";//Somente uma array
            if($selected===NULL && $disabled===NULL){
                foreach ($conteudoA as $key =>$value) { 
                    //---------------------------------------------------------------------------------------------------------------------
                    $markCSS="";
                    //Recupera o primeiro caractere
                    $firstChar = substr($key,0, 1);
                    //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                    if(ord($firstChar)===42){
                        $key= substr($key, 1);
                        $markCSS = "class=\"$classMarkerCSS\"";
                    }
                    //---------------------------------------------------------------------------------------------------------------------
                    $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                }
            }  
            //echo HTMLCLASS::HTML_Select3($arr,NULL,1)."<br>";//Selected NULL e disable INTEGER 
            elseif ($selected===NULL &&(gettype($disabled))==="integer") {
                foreach ($conteudoA as $key =>$value) { 
                    //---------------------------------------------------------------------------------------------------------------------
                    $markCSS="";
                    //Recupera o primeiro caractere
                    $firstChar = substr($key,0, 1);
                    //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                    if(ord($firstChar)===42){
                        $key= substr($key, 1);
                        $markCSS = "class=\"$classMarkerCSS\"";
                    }
                    //---------------------------------------------------------------------------------------------------------------------
                    if($key===$disabled){   
                        $allValues .= "<option $markCSS disabled value=\"" . $key . "\">" . $value . "</option>";
                    }
                    else{
                       $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>"; 
                    }
                    
                }
            }
            //echo HTMLCLASS::HTML_Select3($arr,NULL,"e")."<br>";//Selected NULL e disable string QUE EXISTE
            //echo HTMLCLASS::HTML_Select3($arr,NULL,"b")."<br>";//Selected NULL e disable string QUE NÃO EXISTE
            elseif ($selected===NULL && $disabled!==NULL&&(gettype($disabled))==="string") {                
                //Se string  $disabled existe na lista   
                if(in_array("$disabled", $conteudoA)){//Se a label para ser desabilitada existe
                        foreach ($conteudoA as $key =>$value) { 
                            //---------------------------------------------------------------------------------------------------------------------
                            $markCSS="";
                            //Recupera o primeiro caractere
                            $firstChar = substr($key,0, 1);
                            //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                            if(ord($firstChar)===42){
                                $key= substr($key, 1);
                                $markCSS = "class=\"$classMarkerCSS\"";
                            }
                            //---------------------------------------------------------------------------------------------------------------------
                            if($value===$disabled){
                                $allValues .= "<option $markCSS disabled value=\"" . $key . "\">" . $value . "</option>";
                            }
                            else{
                                $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>"; 
                            }
                            
                        } //Fim de foreach
                    }//Fim de if($disabledExist)                    
                //Se string  $disabled não existe na lista ==> Vai criar no primeiro lugar da lista e desabilitar
                else{ 
                        //--------------------------------------------------------------------------------------------------------------------- 
                        $markCSS=NULL;
                        if($classMarkerCSS<>NULL){$markCSS = "class=\"$classMarkerCSS\"";}
                        //---------------------------------------------------------------------------------------------------------------------
                        $allValues .= "<option $markCSS disabled value=\"0\">" . $disabled . "</option>";
                        
                        foreach ($conteudoA as $key =>$value) {  
                            //---------------------------------------------------------------------------------------------------------------------
                            $markCSS="";
                            //Recupera o primeiro caractere
                            $firstChar = substr($key,0, 1);
                            //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                            if(ord($firstChar)===42){
                                $key= substr($key, 1);
                                $markCSS = "class=\"$classMarkerCSS\"";
                            }
                            //---------------------------------------------------------------------------------------------------------------------
                           $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                        }
                    }
            }
            //echo HTMLCLASS::HTML_Select3($arr,1,NULL)."<br>";//Selected integer e disable NULL
            elseif (gettype($selected)==="integer"&&$disabled===NULL) {                
                    foreach ($conteudoA as $key =>$value) { 
                        
                           //---------------------------------------------------------------------------------------------------------------------
                            $markCSS="";
                            //Recupera o primeiro caractere
                            $firstChar = substr($key,0, 1);
                            //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                            if(ord($firstChar)===42){
                                $key= substr($key, 1);
                                $markCSS = "class=\"$classMarkerCSS\"";
                            }
                            //---------------------------------------------------------------------------------------------------------------------
                        
                            if($key===$selected){
                                $allValues .= "<option $markCSS selected=\"selected\" value=\"" . $key . "\">" . $value . "</option>";
                            }
                            else{
                               $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                            }
                    }
            }         
            //echo HTMLCLASS::HTML_Select3($arr,1,1)."<br>";//Selected integer e disable A MESMA integer
            //echo HTMLCLASS::HTML_Select3($arr,1,2)."<br>";//Selected integer e disable OUTRA integer
            elseif (gettype($selected)==="integer"&&gettype($disabled)==="integer") {                
                    foreach ($conteudoA as $key =>$value) {
                            //---------------------------------------------------------------------------------------------------------------------
                            $markCSS="";
                            //Recupera o primeiro caractere
                            $firstChar = substr($key,0, 1);
                            //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                            if(ord($firstChar)===42){
                                $key= substr($key, 1);
                                $markCSS = "class=\"$classMarkerCSS\"";
                            }
                            //---------------------------------------------------------------------------------------------------------------------                   
                        
                        
                            if($key===$selected){
                                if($key===$disabled){//Sera disabled e selected
                                    $allValues .= "<option $markCSS selected=\"selected\" disabled value=\"" . $key . "\">" . $value . "</option>";
                                }
                                else{//Sera somente  selected
                                    $allValues .= "<option $markCSS selected=\"selected\" value=\"" . $key . "\">" . $value . "</option>";
                                }
                            }
                            else{
                                if($key===$disabled){//Sera disabled 
                                    $allValues .= "<option $markCSS disabled value=\"" . $key . "\">" . $value . "</option>";
                                }
                                else{//Nem disabled nem selected
                                    $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                                }
                            }
                    }
            }
            //echo HTMLCLASS::HTML_Select3($arr,2,"i")."<br>";//Selected integer e disable a mesma string escolhida para Selected
            //echo HTMLCLASS::HTML_Select3($arr,2,"o")."<br>";//Selected integer e disable string q não é a mesma de selected
            //echo HTMLCLASS::HTML_Select3($arr,2,"z")."<br>";//Selected integer e disable string q não existe
            elseif (gettype($selected)==="integer"&&gettype($disabled)==="string") {  
                 if(in_array("$disabled", $conteudoA)){//O disabled esta na array
                     
                            foreach ($conteudoA as $key =>$value) { 
                                //---------------------------------------------------------------------------------------------------------------------
                                $markCSS="";
                                //Recupera o primeiro caractere
                                $firstChar = substr($key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                if(ord($firstChar)===42){
                                    $key= substr($key, 1);
                                    $markCSS = "class=\"$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                                
                                if($key===$selected){//Selecionado
                                    if($value===$disabled){//Sera disabled e selected
                                        $allValues .= "<option $markCSS selected=\"selected\" disabled value=\"" . $key . "\">" . $value . "</option>";
                                    }
                                    else{//Sera somente  selected
                                        $allValues .= "<option $markCSS selected=\"selected\" value=\"" . $key . "\">" . $value . "</option>";
                                    }
                                }
                                else{
                                        if($value===$disabled){//Sera disabled 
                                                $allValues .= "<option $markCSS disabled value=\"" . $key . "\">" . $value . "</option>";
                                        }
                                        else{//Nem disabled nem selected
                                                $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                                        }
                                }
                            }//Fim do foreach            
                 }
                 else{//O disabled não esta na array
                        //--------------------------------------------------------------------------------------------------------------------- 
                        $markCSS=NULL;
                        if($classMarkerCSS<>NULL){$markCSS = "class=\"$classMarkerCSS\"";}
                        //---------------------------------------------------------------------------------------------------------------------
                        $allValues .= "<option $markCSS disabled value=\"0\">" . $disabled . "</option>";
                        foreach ($conteudoA as $key =>$value) { 
                                //---------------------------------------------------------------------------------------------------------------------
                                $markCSS="";
                                //Recupera o primeiro caractere
                                $firstChar = substr($key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                if(ord($firstChar)===42){
                                    $key= substr($key, 1);
                                    $markCSS = "class=\"$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------  
                              if($key===$selected){//Selecionado                                  
                                        $allValues .= "<option $markCSS selected=\"selected\" value=\"" . $key . "\">" . $value . "</option>";                                   
                              } 
                              else{
                                  $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                              }
                        }
                 }      
            }
            //echo HTMLCLASS::HTML_Select3($arr,"e",NULL)."<br>";//Selected string existe e disable NULL
            //echo HTMLCLASS::HTML_Select3($arr,"b",NULL)."<br>";//Selected string não existe e disable NULL
            elseif (gettype($selected)==="string"&&$selected!==NULL&&$disabled===NULL) {     
                if(in_array("$selected", $conteudoA)){
                        foreach ($conteudoA as $key =>$value) { 
                               //---------------------------------------------------------------------------------------------------------------------
                                $markCSS="";
                                //Recupera o primeiro caractere
                                $firstChar = substr($key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                if(ord($firstChar)===42){
                                    $key= substr($key, 1);
                                    $markCSS = "class=\"$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                            
                            if($value===$selected){
                                $allValues .= "<option $markCSS selected=\"selected\" value=\"" . $key . "\">" . $value . "</option>";
                            }
                            else{
                                $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>"; 
                            }
                        } 
                }
                else{ 
                        //--------------------------------------------------------------------------------------------------------------------- 
                        $markCSS=NULL;
                        if($classMarkerCSS<>NULL){$markCSS = "class=\"$classMarkerCSS\"";}
                        //---------------------------------------------------------------------------------------------------------------------
                        $allValues .= "<option selected=\"selected\" value=\"0\">" . $selected . "</option>";
                        foreach ($conteudoA as $key =>$value) {    
                                //---------------------------------------------------------------------------------------------------------------------
                                $markCSS="";
                                //Recupera o primeiro caractere
                                $firstChar = substr($key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                if(ord($firstChar)===42){
                                    $key= substr($key, 1);
                                    $markCSS = "class=\"$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                           $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                        }
                    }
            } 
            //echo HTMLCLASS::HTML_Select3($arr,"e",2)."<br>";//Selected string existe e disable o mesmo elemento
            //echo HTMLCLASS::HTML_Select3($arr,"b",2)."<br>";//Selected string não existe e disable outro elemento
            elseif (gettype($selected)==="string"&&gettype($disabled)==="integer") {
                    if(in_array("$selected", $conteudoA)){//O $selected esta na array
                               foreach ($conteudoA as $key =>$value) { 
                                //---------------------------------------------------------------------------------------------------------------------
                                $markCSS="";
                                //Recupera o primeiro caractere
                                $firstChar = substr($key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                if(ord($firstChar)===42){
                                    $key= substr($key, 1);
                                    $markCSS = "class=\"$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                                   if($value===$selected){//Selecionado
                                       if($key===$disabled){//Sera disabled e selected
                                           $allValues .= "<option $markCSS selected=\"selected\" disabled value=\"" . $key . "\">" . $value . "</option>";
                                       }
                                       else{//Sera somente  selected
                                           $allValues .= "<option $markCSS selected=\"selected\" value=\"" . $key . "\">" . $value . "</option>";
                                       }
                                   }
                                   else{
                                           if($key===$disabled){//Sera disabled 
                                                   $allValues .= "<option $markCSS disabled value=\"" . $key . "\">" . $value . "</option>";
                                           }
                                           else{//Nem disabled nem selected
                                                   $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                                           }
                                   }
                               }//Fim do foreach            
                    }
                    else{//O $selected não esta na array
                            //--------------------------------------------------------------------------------------------------------------------- 
                            $markCSS=NULL;
                            if($classMarkerCSS<>NULL){$markCSS = "class=\"$classMarkerCSS\"";}
                            //---------------------------------------------------------------------------------------------------------------------
                           $allValues .= "<option $markCSS selected=\"selected\" value=\"0\">" . $selected . "</option>";
                           foreach ($conteudoA as $key =>$value) { 
                                //---------------------------------------------------------------------------------------------------------------------
                                $markCSS="";
                                //Recupera o primeiro caractere
                                $firstChar = substr($key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                if(ord($firstChar)===42){
                                    $key= substr($key, 1);
                                    $markCSS = "class=\"$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                                 if($key===$disabled){//Selecionado                                  
                                           $allValues .= "<option $markCSS disabled value=\"" . $key . "\">" . $value . "</option>";                                   
                                 } 
                                 else{
                                     $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                                 }
                           }
                    }      
            }
            //echo HTMLCLASS::HTML_Select3($arr,"e","b")."<br>";//Selected string existe e disable um elemento que não existe
            //echo HTMLCLASS::HTML_Select3($arr,"b","e")."<br>";//Selected string não existe e disable um elemento que existe
            //echo HTMLCLASS::HTML_Select3($arr,"b","b")."<br>";//Selected string não existe e disable o mesmo elemento - que não existe
            //echo HTMLCLASS::HTML_Select3($arr,"b","p")."<br>";//Selected string não existe e disable outro elemento que não existe
            elseif (gettype($selected)==="string"&&gettype($disabled)==="string") {
                    if(in_array("$selected", $conteudoA)){//O $selected esta na array
                                if(in_array("$disabled", $conteudoA)){//$selected e  $disabled estão na array
                                        foreach ($conteudoA as $key =>$value){
                                                //---------------------------------------------------------------------------------------------------------------------
                                                $markCSS="";
                                                //Recupera o primeiro caractere
                                                $firstChar = substr($key,0, 1);
                                                //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                                if(ord($firstChar)===42){
                                                    $key= substr($key, 1);
                                                    $markCSS = "class=\"$classMarkerCSS\"";
                                                }
                                                //---------------------------------------------------------------------------------------------------------------------
                                                if($value===$selected){//$val =  $selected       
                                                    if($value===$disabled){//$val =  $selected  &&   $val =  $disabled                            
                                                            $allValues .= "<option $markCSS selected=\"selected\" disabled value=\"" . $key . "\">" . $value . "</option>";                                   
                                                    }
                                                    else{//$val =  $selected  &&   $val <>  $disabled 
                                                            $allValues .= "<option $markCSS selected=\"selected\" value=\"" . $key . "\">" . $value . "</option>";
                                                    }                                                                                           
                                                } 
                                                else{//$val <>  $selected
                                                        if($value===$disabled){//$val <>  $selected  &&   $val =  $disabled                            
                                                                $allValues .= "<option  $markCSS disabled value=\"" . $key . "\">" . $value . "</option>";                                   
                                                        }
                                                        else{//$val <>  $selected  &&   $val <>  $disabled 
                                                                $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                                                        }
                                                }
                                        }
                                }
                                 else{//$selected esta e $disabled não estão na array
                                     //--------------------------------------------------------------------------------------------------------------------- 
                                    $markCSS=NULL;
                                    if($classMarkerCSS<>NULL){$markCSS = "class=\"$classMarkerCSS\"";}
                                    //---------------------------------------------------------------------------------------------------------------------
                                        $allValues .= "<option $markCSS disabled>" . $disabled . "</option>";
                                        foreach ($conteudoA as $key =>$value){
                                                //---------------------------------------------------------------------------------------------------------------------
                                                $markCSS="";
                                                //Recupera o primeiro caractere
                                                $firstChar = substr($key,0, 1);
                                                //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                                if(ord($firstChar)===42){
                                                    $key= substr($key, 1);
                                                    $markCSS = "class=\"$classMarkerCSS\"";
                                                }
                                                //---------------------------------------------------------------------------------------------------------------------
                                                if($value===$selected){//$val =  $selected       
                                                        $allValues .= "<option $markCSS selected=\"selected\" value=\"" . $key . "\">" . $value . "</option>";
                                                } 
                                                else{//$val <>  $selected
                                                        $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                                                }
                                        }
                                 }             
                    }
                    else{//O $selected não está na array
                        
                                if(in_array("$disabled", $conteudoA)){//$selected não está e  $disabled está na array
                                        //--------------------------------------------------------------------------------------------------------------------- 
                                        $markCSS=NULL;
                                        if($classMarkerCSS<>NULL){$markCSS = "class=\"$classMarkerCSS\"";}
                                        //---------------------------------------------------------------------------------------------------------------------
                                        $allValues .= "<option $markCSS selected=\"selected\" >" . $selected . "</option>";
                                        foreach ($conteudoA as $key =>$value){
                                                   //---------------------------------------------------------------------------------------------------------------------
                                                    $markCSS="";
                                                    //Recupera o primeiro caractere
                                                    $firstChar = substr($key,0, 1);
                                                    //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                                    if(ord($firstChar)===42){
                                                        $key= substr($key, 1);
                                                        $markCSS = "class=\"$classMarkerCSS\"";
                                                    }
                                                    //---------------------------------------------------------------------------------------------------------------------
                                                   if($value===$disabled){//$val =  $selected  &&   $val =  $disabled                            
                                                            $allValues .= "<option $markCSS disabled value=\"" . $key . "\">" . $value . "</option>";                                   
                                                    }
                                                    else {
                                                            $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>"; 
                                                    }
                                        }
                                }
                                else{//$selected e $disabled não estão na array
                                        if($selected===$disabled){
                                            //--------------------------------------------------------------------------------------------------------------------- 
                                            $markCSS=NULL;
                                            if($classMarkerCSS<>NULL){$markCSS = "class=\"$classMarkerCSS\"";}
                                            //---------------------------------------------------------------------------------------------------------------------
                                            $allValues .= "<option $markCSS value=\"0\" selected=\"selected\" disabled >" . $selected . "</option>";//Valor = 0 para strings que não existem na lista e estao desabilitadas
                                        }
                                        else{
                                            //--------------------------------------------------------------------------------------------------------------------- 
                                            $markCSS=NULL;
                                            if($classMarkerCSS<>NULL){$markCSS = "class=\"$classMarkerCSS\"";}
                                            //---------------------------------------------------------------------------------------------------------------------
                                            $allValues .= "<option $markCSS selected=\"selected\" >" . $selected . "</option>";
                                            $allValues .= "<option $markCSS disabled >" . $disabled . "</option>";
                                        }                             
                                        foreach ($conteudoA as $key =>$value){     
                                            //---------------------------------------------------------------------------------------------------------------------
                                            $markCSS="";
                                            //Recupera o primeiro caractere
                                            $firstChar = substr($key,0, 1);
                                            //Testa se está marcado com um *, afeta o controlador $mark para verdadeiro e limpa a string $key
                                            if(ord($firstChar)===42){
                                                $key= substr($key, 1);
                                                $markCSS = "class=\"$classMarkerCSS\"";
                                            }
                                            //---------------------------------------------------------------------------------------------------------------------
                                            $allValues .= "<option $markCSS value=\"" . $key . "\">" . $value . "</option>";
                                        }
                                } 
                    }      
            } 
            
            if(isset($multiple)){
                return "<select multiple " . $attrSelect . ">" . $allValues . "</select>"; 
            }
            else{
               return "<select " . $attrSelect . ">" . $allValues . "</select>"; 
            }
            
        }
    }
}

class USER_T  {


static function USER_T_Select(){
return dbBase::MySql_Select(USER_T);
}
static function USER_T_Insert($values) {
return dbBase::Insert_Geral("USER_T", $values, $est);
}
static function USER_T_Content() {
echo "<br/>";
echo "<pre>";
print_r(self::USER_T_Select());
echo "</pre>";
echo "<br/>";
}
static function Input_txt_PK_USER(array $attr=NULL){
$arrAttrInt=array();
$arrAttrInt["id"]="txt_PK_USER";
$arrAttrInt["class"]="";
$arrAttrInt["type"]="text";
$arrAttrInt["value"]="";
if(is_null($attr)){
$arrAttr=$arrAttrInt;
}
else{
$arrAttr=  array_merge($arrAttrInt,$attr);
}
return dbBase::HTML_Input($arrAttr);
}
static function InputB_txt_PK_USER(array $attr=NULL){
$arrAttrInt=array();
$arrAttrInt["id"]="txt_PK_USER";
$arrAttrInt["class"]="";
$arrAttrInt["type"]="text";
$arrAttrInt["value"]="";
if(is_null($attr)){
$arrAttr=$arrAttrInt;
}
else{
$arrAttr=  array_merge($arrAttrInt,$attr);
}
return "<label class=\"input\">".dbBase::HTML_Input($arrAttr)."</label>";
}
static function Input_txt_NAME(array $attr=NULL){
$arrAttrInt=array();
$arrAttrInt["id"]="txt_NAME";
$arrAttrInt["class"]="";
$arrAttrInt["type"]="text";
$arrAttrInt["value"]="";
if(is_null($attr)){
$arrAttr=$arrAttrInt;
}
else{
$arrAttr=  array_merge($arrAttrInt,$attr);
}
return dbBase::HTML_Input($arrAttr);
}
static function InputB_txt_NAME(array $attr=NULL){
$arrAttrInt=array();
$arrAttrInt["id"]="txt_NAME";
$arrAttrInt["class"]="";
$arrAttrInt["type"]="text";
$arrAttrInt["value"]="";
if(is_null($attr)){
$arrAttr=$arrAttrInt;
}
else{
$arrAttr=  array_merge($arrAttrInt,$attr);
}
return "<label class=\"input\">".dbBase::HTML_Input($arrAttr)."</label>";
}
static function Input_txt_LASTNAME(array $attr=NULL){
$arrAttrInt=array();
$arrAttrInt["id"]="txt_LASTNAME";
$arrAttrInt["class"]="";
$arrAttrInt["type"]="text";
$arrAttrInt["value"]="";
if(is_null($attr)){
$arrAttr=$arrAttrInt;
}
else{
$arrAttr=  array_merge($arrAttrInt,$attr);
}
return dbBase::HTML_Input($arrAttr);
}
static function InputB_txt_LASTNAME(array $attr=NULL){
$arrAttrInt=array();
$arrAttrInt["id"]="txt_LASTNAME";
$arrAttrInt["class"]="";
$arrAttrInt["type"]="text";
$arrAttrInt["value"]="";
if(is_null($attr)){
$arrAttr=$arrAttrInt;
}
else{
$arrAttr=  array_merge($arrAttrInt,$attr);
}
return "<label class=\"input\">".dbBase::HTML_Input($arrAttr)."</label>";
}
static function Obj_Select_USER_T($attr = NULL, $selected = NULL, $disabled = NULL) {
$arr=  dbBase::MySql_Select(USER_T,LASTNAME);
return dbBase::HTML_Select($arr, $selected,$disabled,$attr,$multiple);
}


}

?>