<?php
$time_start=microtime(true);
$files = array('charleston_road','opera','rue_de_londres','lets_go_higher');

$backbone_r=array();
$backbone_c=array();
$router_r=array();
$router_c=array();
$grid=array();
	
	/*
	 * each cell has 8 neighbors:
	 * -1,-1
	 * -1, 0
	 * -1, 1
	 *  0,-1
	 *  0, 1
	 *  1,-1
	 *  1, 0
	 *  1, 1
	 * */

function target($r,$c,&$grid, $R, $H, $W, $save){
	if($grid[$r][$c]=='#')
		return 0;
	$target_cells=0;
	for($h=1;$h>-2;$h-=2){
	for($k=1;$k>-2;$k-=2){
		$max_x=$R;
		$max_y=$R;		
		
		for($x=0;$x<=$max_x;$x++){
			for($y=0;$y<=$max_y;$y++){
				if($r+$k*$x>=0 and $r+$k*$x<$H and $c+$h*$y>=0 and $c+$h*$y<$W){
					if($grid[$r+$k*$x][$c+$h*$y]=='.'){
						if($save==1){
							$grid[$r+$k*$x][$c+$h*$y] = 't';
						}
						$target_cells++;
					}elseif($grid[$r+$k*$x][$c+$h*$y]=='#'){
						if($y=0){
							$max_x=$x-1;
						}else{
							$max_y=$y-1;
						}
					}
				}
			}
		}
	}
	}
	return $target_cells;
};

function backbone($r,$c,&$backbone_r, &$backbone_c, $save){
	$temp_max=max(abs($r-$backbone_r[0]),abs($c-$backbone_c[0]));
	$best=0;
	foreach($backbone_r as $key => $value){
		$temp=max(abs($r-$value),abs($c-$backbone_c[$key]));
		if($temp<$temp_max){
			$temp_max=$temp;
			$best=$key;
		}
	}
	if($save==1){
		while($temp_max>0){
			
			if($r>$backbone_r[$best]){
				array_push($backbone_r, ($backbone_r[$best]+1));
			}elseif($r<$backbone_r[$best]){
				array_push($backbone_r, ($backbone_r[$best]-1));
			}else{
				array_push($backbone_r, ($backbone_r[$best]));
			}
			
			if($c>$backbone_c[$best]){
				array_push($backbone_c, ($backbone_c[$best]+1));
			}elseif($c<$backbone_c[$best]){
				array_push($backbone_c, ($backbone_c[$best]-1));
			}else{
				array_push($backbone_c, ($backbone_c[$best]));
			}
			
			$best=(count($backbone_r)-1);
			if($best!=(count($backbone_c)-1)){
				echo "\nERRORE\n";
			}
			$temp_max=max(abs($r-$backbone_r[$best]),abs($c-$backbone_c[$best]));
		}
		return (count($backbone_r)-1);
	}else{
		return $temp_max;
	}
	return 0;
};
	
$folog=fopen("run.log","w");
fclose($folog);

foreach($files as $fname){
	
	unset($backbone_r);
	$backbone_r=array();
	unset($backbone_c);
	$backbone_c=array();
	unset($router_r);
	$router_r=array();
	unset($router_c);
	$router_c=array();
	unset($grid);
	$grid=array();
	
	/*
	 * H rows
	 * W columns
	 * [r,c] cell position
	 * cell can be:
	 * . target
	 * - void
	 * # wall
	 * b backbone
	 * 
	 * R router coverage radius
	 * Pr placing single router costs
	 * Pb single cell to backbone price
	 * B budget
	 */
	 
	$inputfile=$fname.".in";
	$riga=0;
	$f=file($inputfile);
	$tmp=str_replace(array("\n","\r"), "",explode(" ",$f[$riga++]));
	
	$H=$tmp[0];
	$W=$tmp[1];
	$R=$tmp[2];
	
	$tmp=str_replace(array("\n","\r"), "",explode(" ",$f[$riga++]));
	
	$Pb=$tmp[0];
	$Pr=$tmp[1];
	$B=$tmp[2];
	
	$tmp=str_replace(array("\n","\r"), "",explode(" ",$f[$riga++]));
	
	$br=$tmp[0];
	$bc=$tmp[1];
	
	$backbone_r[0]=$br;
	$backbone_c[0]=$bc;
	
	
	for($r=0; $r<$H;$r++){
		$tmp=str_replace(array("\n","\r"), "",str_split($f[$riga++],1));
		for($c=0; $c<$W;$c++){
			$grid[$r][$c]=$tmp[$c];
		}
	}
	
	$folog=fopen("run.log","a");
	fputs($folog,"Running ".$fname." file\n");
	fputs($folog,"Columns=$H\n");
	fputs($folog,"Rows=$W\n");
	fputs($folog,"Radius=$R\n");
	fputs($folog,"Budget=$B\n");
	fputs($folog,"Pb=$Pb\n");
	fputs($folog,"Pr=$Pr\n");
	fclose($folog);
	
	echo "Running ".$fname." file\n";
	echo "Columns=$H\n";
	echo "Rows=$W\n";
	echo "Radius=$R\n";
	
	echo "Budget=$B\n";
	echo "Pb=$Pb\n";
	echo "Pr=$Pr\n";

	$routers=0;
	$total_coverage=0;
	$total_backbone=0;
	
	$max_coverage=(2*$R+1)**2;
	while ((($routers+1)*$Pr+$total_backbone*$Pb<$B)){
		$best_coverage=0;
		$best_r=-1;
		$best_c=-1;

		for($r=0; $r<$H and (($routers+1)*$Pr+$total_backbone*$Pb<$B);$r++){
			for($c=0; $c<$W and (($routers+1)*$Pr+$total_backbone*$Pb<$B);$c++){
				foreach(array_keys($router_r,$r) as $rKey){
					if($router_c[$rKey]==$c){
						continue;
					}
				}
				if($grid[$r][$c]!='#' and $grid[$r][$c]!='t'){
					$coverage=target($r,$c,$grid, $R, $H, $W, 0); //1 writes on the grid, 0 don't
					//echo "Router in ".$r.", ".$c." covers ".$coverage." celle.\n";
					if($coverage>=$max_coverage){
						$count_b=backbone($r,$c,$backbone_r, $backbone_c, 0); //1 writes on the grid, 0 don't
						
						if((($routers+1)*$Pr+($total_backbone+$count_b)*$Pb) < $B){
							array_push($router_r, $r);
							array_push($router_c, $c);
							$routers++;
							$total_coverage+=target($r,$c,$grid, $R, $H, $W, 1); //1 writes on the grid, 0 don't
							$total_backbone=backbone($r,$c,$backbone_r, $backbone_c, 1); //1 writes on the grid, 0 don't
							if($total_backbone==0)
								echo "error:".$r." ".$c."-backbone returned 0\n";
							if((($routers)*$Pr+$total_backbone*$Pb)>=$B){
								echo "ERROR:".$r." ".$c."-budget exceed\n";
								echo "ERROR:".$routers." * ".$Pr." =".($routers*$Pr)."\n";
								echo "ERROR:".$total_backbone." * ".$Pb." =".($total_backbone*$Pb)."\n";
								echo "ERROR:".$count_b."\n";
							}
							//echo "\n";
							//echo "new reuter:".$r.$c." score:".(1000*$total_coverage+$B-(($routers+1)*$Pr+$total_backbone*$Pb))."\n";
							//echo "new reuter:".$r.$c." budget available:".($B-($routers*$Pr+$total_backbone*$Pb))."\n";
						}
					}elseif($best_coverage<$coverage){
						$best_coverage=$coverage;
						$best_r=$r;
						$best_c=$c;
					}
				}
			}
		}
		if( $best_r != -1 and $best_c != -1){
		$count_b=backbone($best_r,$best_c,$backbone_r, $backbone_c, 0);
			if((($routers+1)*$Pr+($total_backbone+$count_b)*$Pb)<$B){
				array_push($router_r, $best_r);
				array_push($router_c, $best_c);
				$routers++;
				$total_coverage+=target($best_r,$best_c,$grid, $R, $H, $W, 1); //1 scrive;
				$total_backbone=backbone($best_r,$best_c,$backbone_r, $backbone_c, 1);
				if($total_backbone==0)
					echo "error:".$r." ".$c."-backbone returned 0\n";
				if((($routers)*$Pr+$total_backbone*$Pb)>=$B){
					echo "ERROR:".$r." ".$c."-budget exceed\n";
					echo "ERROR:".$routers." * ".$Pr." =".($routers*$Pr)."\n";
					echo "ERROR:".$total_backbone." * ".$Pb." =".($total_backbone*$Pb)."\n";
					echo "ERROR:".$count_b."\n";
				}
				//echo "\n";
				//echo "new reuter:".$best_r.$best_c." score:".(1000*$total_coverage+$B-(($routers+1)*$Pr+$total_backbone*$Pb))."\n";
				//echo "new reuter:".$best_r.$best_c." budget available:".($B-($routers*$Pr+$total_backbone*$Pb))."\n";
			}else{
				break;
			}	
		}
		if($max_coverage==1)
			break;
		$max_coverage=max(($max_coverage-30),1);
		$folog=fopen("run.log","a");
		fputs($folog,"end of cycles r/c. budget available:".($B-($routers*$Pr+$total_backbone*$Pb))."next maxcov:".$max_coverage."\n");
		fclose($folog);
		//echo "end of cycles r/c. budget available:".($B-($routers*$Pr+$total_backbone*$Pb))."next maxcov:".$max_coverage."\n";
	}
	$folog=fopen("run.log","a");
	fputs($folog,"score:".(1000*$total_coverage+$B-(($routers+1)*$Pr+$total_backbone*$Pb))."\n");
	fputs($folog,"score:".(1000*$total_coverage+$B-(($routers+1)*$Pr+$total_backbone*$Pb))."\n");
	fclose($folog);
	echo "score:".(1000*$total_coverage+$B-(($routers+1)*$Pr+$total_backbone*$Pb))."\n";
	echo "Remaining Budget:".($B-($routers*$Pr+count($backbone_r)*$Pb))."\n";
	
	$fo=fopen($fname.".out","w");
	fputs($fo,($total_backbone)."\n");
	foreach($backbone_r as $bKey => $bVal){
		if($bKey!=0)
			fputs($fo,$bVal." ".$backbone_c[$bKey]."\n");
	}
	
	fputs($fo,$routers."\n");
	foreach($router_r as $rKey => $rVal){
		fputs($fo,$rVal." ".$router_c[$rKey]."\n");
	}
	echo "printed file ". $fname.".out\n";	
	fclose($fo);
	
	// print .map file
	$fo=fopen($fname.".map","w");
	for($r=0; $r<$H;$r++){
		for($c=0; $c<$W;$c++){
			foreach(array_keys($backbone_r,$r) as $bKey){
				if($backbone_c[$bKey]==$c){
					$grid[$r][$c]="b";
				}
			}
			fputs($fo,$grid[$r][$c]);
		}
		fputs($fo,"\n");	
	}
	echo "printed file ". $fname.".map\n";	
	fclose($fo);
	
	
	$time_end=microtime(true);
	echo "execution time: ".(($time_end-$time_start)/60)."\n";
 }

?>
