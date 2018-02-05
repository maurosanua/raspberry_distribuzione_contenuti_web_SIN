<?php

if(isset($_GET['id']) && is_numeric($_GET['id'])){$id = $_GET['id'];} else {$id = 0;}

global $user_login_obj;
if(!isset($user_login_obj) || !is_a($user_login_obj, "user")){
	$user_login_obj = new user(2);
}

global $sezione;
if(!isset($sezione)){
	$sezione=0;
}


global $sotto_sezione;
if(!isset($sezione)){
	$sezione=0;
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Template Website</title>

    <!-- Bootstrap Core CSS -->
    <link href="bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="bower_components/metisMenu/dist/metisMenu.min.css" rel="stylesheet">
	
	 <!-- Custom Fonts -->
    <link href="bower_components/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- Timeline CSS -->
    <link href="dist/css/timeline.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">	
   

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

	  <!-- jQuery -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>
	
	<script type="text/javascript" src="js/form_upload/jquery.form.js"></script>
	<script type="text/javascript" src="js/form_upload/jquery.form.min.js"></script>
	<script type="text/javascript" src="js/upload_file.js"></script>

	
	
    <!-- Custom Theme JavaScript -->
    <script src="dist/js/sb-admin-2.js"></script>
	
    <link href="css/global_admin.css" rel="stylesheet">

	<link class="include" rel="stylesheet" type="text/css" href="css/jquery-ui-1.10.4.custom.css" />
	
	<script src="js/jquery-ui-1.10.4.custom.js"></script>
	
	<!--librerire per gestire il datepicker con la notazione italiana-->
	<script src="js/ui.datepicker-it.js"></script>
	

	<script type="text/javascript" src="js/lib.js"></script>
	<script type="text/javascript" src="js/lib_form.js"></script>
	<script type="text/javascript" src="js/lib_elenco.js"></script>
	
	
	<!--includiamo i file per l'editor html-->
	<link href="plugin/editor_html/summernote.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="plugin/editor_html/summernote.js"></script>
	

	<link href="css/stile.css" rel="stylesheet">
</head>

<body>

    <div id="wrapper">

        <!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
			
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
				
<!--					<a class="navbar-brand" href="index.php"><img width="80" height="80" src="../../img/logo-mff-basic.png"/></a>-->
				
            </div>
            <!-- /.navbar-header -->

            <ul class="nav navbar-top-links navbar-right">
				
                <!-- /.dropdown -->
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-user fa-fw"></i>  <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li>
							<a href="gestione_account.php" <?=($sezione==8)?"class='active'":""?>><i class="fa fa-user fa-fw"></i> Account</a>
                        </li>
<?
							//if($user_login_obj->can_view_impostazioni()){
							/*
?>	
								<li>
									<a href="settings.php"><i class="fa fa-gear fa-fw"></i> Impostazioni</a>
								</li>
<?
							 * 
							 */
							//}
?>
						
                        <li class="divider"></li>
                        <li><a href="login.php?logout=1"><i class="fa fa-sign-out fa-fw"></i>Logout</a>
                        </li>
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
            </ul>
            <!-- /.navbar-top-links -->
			
            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li class="sidebar-search">

                            <!-- /input-group -->
                        </li>
						
							<li>
								<a href="index.php" <?=($sezione==0)?"class='active'":""?>><i class="fa fa-archive"></i> Box</a>
							</li>
														
							<li>
								<a href="utenti.php" <?=($sezione==4)?"class='active'":""?>><i class="fa fa-users"></i> Gestione Utenti</a>
							</li>
														
							<li>
							<a href="#" <?=($sezione==5)?"class='active'":""?>><i class="fa fa-cogs"></i> Settings</a>
							
							
							<ul class="nav nav-second-level ">
								<li>
									<a href="#"><i class="fa fa-file-text-o"></i> Pagine Default</a>
								</li>
							</ul>
							</li>
							
							<li>
								<a href="login.php?logout=1"><i class="fa fa-sign-out fa-fw"></i>Logout</a>
							</li>
									
                        
                    </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
        </nav>
		
		<div class="modal fade" id="modal_global" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">

					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
						<h4 class="modal-title" id="myModalLabel">Finestra generica</h4>
					</div>

					<div class="row">
						<div class="col-lg-12">
							<div class="panel-body">
								<div class="row">
									<div class="col-lg-12">
										Testo generico finestra modale
									</div>
								</div>
							</div>

							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
								<button type="button" class="btn btn-primary" id="" onclick="export_ticket()">Salva</button>
							</div>
						</div>
					</div>
				
				</div>
			</div>
		</div>
