<?php



include('Teacher/Examination.php');

require_once('class/class.phpmailer.php');

$exam = new Examination;

$current_datetime = date("Y-m-d") . ' ' . date("H:i:s", STRTOTIME(date('h:i:sa')));

if(isset($_POST['page']))
{
	if($_POST['page'] == 'register')
	{
		if($_POST['action'] == 'check_email')
		{
			$exam->query = "
			SELECT * FROM user_table
			WHERE user_email_address = '".trim($_POST["email"])."'
			";

			$total_row = $exam->total_row();

			if($total_row == 0)
			{
				$output = array(
					'success'	=>	true
				);

				echo json_encode($output);
			}
		}

		if($_POST['action'] == 'register')
		{
			$user_verification_code = md5(rand());

			$receiver_email = $_POST['user_email_address'];

			$exam->data = array(
				':user_email_address'		=>	$receiver_email,
				':user_password'			=>	password_hash($_POST['user_password'], PASSWORD_DEFAULT),
				':user_verfication_code'	=>	$user_verification_code,

				':user_created_on'			=>	$current_datetime
			);

			$exam->query = "
			INSERT INTO user_table
			(user_email_address, user_password, user_verfication_code,  user_created_on)
			VALUES
			(:user_email_address, :user_password, :user_verfication_code,  :user_created_on)
			";

			$exam->execute_query();

			$output = array(
				'success'	=>	true
			);

			echo json_encode($output);
		}
	}

	if($_POST['page'] == 'login')
	{
		if($_POST['action'] == 'login')
		{
			$exam->data = array(
				':user_email_address'	=>	$_POST['user_email_address']
			);

			$exam->query = "
			SELECT * FROM user_table
			WHERE user_email_address = :user_email_address
			";

			$total_row = $exam->total_row();

			if($total_row > 0)
			{
				$result = $exam->query_result();

				foreach($result as $row)
				{
					if($row['user_email_verified'] == 'no')
					{
						if(password_verify($_POST['user_password'], $row['user_password']))
						{
							$_SESSION['user_id'] = $row['user_id'];

							$output = array(
								'success'	=>	true
							);
						}
						else
						{
							$output = array(
								'error'		=>	'Wrong Password'
							);
						}
					}
					else
					{
						$output = array(
							'error'		=>	'Your Email is not verify'
						);
					}
				}
			}
			else
			{
				$output = array(
					'error'		=>	'Wrong Email Address'
				);
			}

			echo json_encode($output);
		}

	}

	if($_POST['page'] == 'index')
	{
		if($_POST['action'] == "fetch_exam")
		{
			$exam->query = "
			SELECT * FROM online_exam_table
			WHERE online_exam_id = '".$_POST['exam_id']."'
			";

			$result = $exam->query_result();

			$output = '
			<div class="card">
				<div class="card-header">Exam Details</div>
				<div class="card-body">
					<table class="table table-striped table-hover table-bordered">
			';
			foreach($result as $row)
			{
				$output .= '


			<style>

			tr{
				font-size: 14px;
				color: red;
			}



     .card{
			position: relative;
			right:300px;

		}









			</style>
				<tr>
					<td><b>Exam Title</b></td>
					<td>'.$row["online_exam_title"].'</td>
				</tr>
				<tr>
					<td><b>Exam Date & Time</b></td>
					<td>'.$row["online_exam_datetime"].'</td>
				</tr>
				<tr>
					<td><b>Exam Duration</b></td>
					<td>'.$row["online_exam_duration"].' Minute</td>
				</tr>
				<tr>
					<td><b>Exam Total Question</b></td>
					<td>'.$row["total_question"].' </td>
				</tr>
				<tr>
					<td><b>Marks Per Right Answer</b></td>
					<td>'.$row["marks_per_right_answer"].' Mark</td>
				</tr>
				<tr>
					<td><b>Marks Per Wrong Answer</b></td>
					<td>-'.$row["marks_per_wrong_answer"].' Mark</td>
				</tr>
				';
				if($exam->If_user_already_enroll_exam($_POST['exam_id'], $_SESSION['user_id']))
				{


					$enroll_button = '
					<tr>
						<td colspan="2" align="center">
							<button type="button" name="enroll_button" class="btn btn-danger">You Already Enroll it</button>
						</td>
					</tr>
					';
				}
				else
				{
					$enroll_button = '
					<tr>
						<td colspan="2" align="center">
							<button type="button" name="enroll_button" id="enroll_button" class="btn btn-danger" data-exam_id="'.$row['online_exam_id'].'">Enroll it</button>
						</td>
					</tr>
					';
				}
				$output .= $enroll_button;
			}

			$output .= '</table>';
			echo $output;
		}
	}


		if($_POST['action'] == 'enroll_exam')
		{
			$exam->data = array(
				':user_id'		=>	$_SESSION['user_id'],
				':exam_id'		=>	$_POST['exam_id']
			);

			$exam->query = "
			INSERT INTO user_exam_enroll_table
			(user_id, exam_id)
			VALUES (:user_id, :exam_id)
			";

			$exam->execute_query();

			$exam->query = "
			SELECT question_id FROM question_table
			WHERE online_exam_id = '".$_POST['exam_id']."'
			";
			$result = $exam->query_result();
			foreach($result as $row)
			{
				$exam->data = array(
					':user_id'				=>	$_SESSION['user_id'],
					':exam_id'				=>	$_POST['exam_id'],
					':question_id'			=>	$row['question_id'],
					':user_answer_option'	=>	'0',
					':marks'				=>	'0'
				);

				$exam->query = "
				INSERT INTO user_exam_question_answer
				(user_id, exam_id, question_id, user_answer_option, marks)
				VALUES (:user_id, :exam_id, :question_id, :user_answer_option, :marks)
				";
				$exam->execute_query();
			}
		}


	if($_POST["page"] == 'enroll_exam')
	{
		if($_POST['action'] == 'fetch')
		{
			$output = array();

			$exam->query = "
			SELECT * FROM user_exam_enroll_table
			INNER JOIN online_exam_table
			ON online_exam_table.online_exam_id = user_exam_enroll_table.exam_id
			WHERE user_exam_enroll_table.user_id = '".$_SESSION['user_id']."'
			AND (";

			if(isset($_POST["search"]["value"]))
			{
			 	$exam->query .= 'online_exam_table.online_exam_title LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.online_exam_datetime LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.online_exam_duration LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.total_question LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.marks_per_right_answer LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.marks_per_wrong_answer LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.online_exam_status LIKE "%'.$_POST["search"]["value"].'%" ';
			}

			$exam->query .= ')';

			if(isset($_POST["order"]))
			{
				$exam->query .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
			}
			else
			{
				$exam->query .= 'ORDER BY online_exam_table.online_exam_id DESC ';
			}

			$extra_query = '';

			if($_POST["length"] != -1)
			{
			 	$extra_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
			}

			$filterd_rows = $exam->total_row();

			$exam->query .= $extra_query;

			$result = $exam->query_result();

			$exam->query = "
			SELECT * FROM user_exam_enroll_table
			INNER JOIN online_exam_table
			ON online_exam_table.online_exam_id = user_exam_enroll_table.exam_id
			WHERE user_exam_enroll_table.user_id = '".$_SESSION['user_id']."'";

			$total_rows = $exam->total_row();

			$data = array();

			foreach($result as $row)
			{
				$sub_array = array();
				$sub_array[] = html_entity_decode($row["online_exam_title"]);
				$sub_array[] = $row["online_exam_datetime"];
				$sub_array[] = $row["online_exam_duration"] . ' Minute';
				$sub_array[] = $row["total_question"] . ' Question';
				$sub_array[] = $row["marks_per_right_answer"] . ' Mark';
				$sub_array[] = '-' . $row["marks_per_wrong_answer"] . ' Mark';
				$status = '';

				if($row['online_exam_status'] == 'Created')
				{
					$status = '<span class="badge badge-danger">Created</span>';
				}

				if($row['online_exam_status'] == 'Started')
				{
					$status = '<span class="badge badge-primary">Started</span>';

				}

				if($row['online_exam_status'] == 'Completed')
				{
					$status = '<span class="badge badge-warning">Completed</span>';
				}

				$sub_array[] = $status;

				if($row["online_exam_status"] == 'Started')
				{
					$view_exam = '<a href="view_exam.php?code='.$row["online_exam_code"].'" class="btn btn-info btn-sm">View Exam</a>';
				}
				if($row["online_exam_status"] == 'Completed')
				{
					$view_exam = '<a href="view_exam.php?code='.$row["online_exam_code"].'" class="btn btn-info btn-sm">View Exam</a>';
				}
	 // $sub_array[] = $view_exam

				$data[] = $sub_array;


		}





			$output = array(
			 	"draw"    			=> 	intval($_POST["draw"]),
			 	"recordsTotal"  	=>  $total_rows,
			 	"recordsFiltered" 	=> 	$filterd_rows,
			 	"data"    			=> 	$data
			);
			echo json_encode($output);
		}
}


	if($_POST['page'] == 'view_exam')
	{
		if($_POST['action'] == 'load_question')
		{
			if($_POST['question_id'] == '')
			{
				$exam->query = "
				SELECT * FROM question_table
				WHERE online_exam_id = '".$_POST["exam_id"]."'
				ORDER BY question_id ASC
				LIMIT 1
				";
			}
			else
			{
				$exam->query = "
				SELECT * FROM question_table
				WHERE question_id = '".$_POST["question_id"]."'
				";
			}

			$result = $exam->query_result();

			$output = '';

			foreach($result as $row)
			{
				$output .= '
				<h1>'.$row["question_title"].'</h1>
				<hr />
				<br />
				<div class="row">
				';

				$exam->query = "
				SELECT * FROM option_table
				WHERE question_id = '".$row['question_id']."'
				";
				$sub_result = $exam->query_result();

				$count = 1;

				foreach($sub_result as $sub_row)
				{
					$output .= '
					<div class="col-md-6" style="margin-bottom:32px;">
						<div class="radio">
							<label><h4><input type="radio" name="option_1" class="answer_option" data-question_id="'.$row["question_id"].'" id-data="'.$count.'"/>&nbsp;'.$sub_row["option_title"].'</h4></label>
						</div>
					</div>
					';

					$count = $count + 1;
				}
				$output .= '
				</div>
				';
				$exam->query = "
				SELECT question_id FROM question_table
				WHERE question_id < '".$row['question_id']."'
				AND online_exam_id = '".$_POST["exam_id"]."'
				ORDER BY question_id DESC
				LIMIT 1";

				$previous_result = $exam->query_result();

				$previous_id = '';
				$next_id = '';

				foreach($previous_result as $previous_row)
				{
					$previous_id = $previous_row['question_id'];
				}

				$exam->query = "
				SELECT question_id FROM question_table
				WHERE question_id > '".$row['question_id']."'
				AND online_exam_id = '".$_POST["exam_id"]."'
				ORDER BY question_id ASC
				LIMIT 1";

  				$next_result = $exam->query_result();

  				foreach($next_result as $next_row)
				{
					$next_id = $next_row['question_id'];
				}

				$if_previous_disable = '';
				$if_next_disable = '';

				if($previous_id == "")
				{
					$if_previous_disable = 'disabled';
				}

				if($next_id == "")
				{
					$if_next_disable = 'disabled';
				}

				$output .= '
					<br /><br />
				  	<div align="center">
				   		<button type="button" name="previous" class="btn btn-info btn-lg previous" id="'.$previous_id.'" '.$if_previous_disable.'>Previous</button>
				   		<button type="button" name="next" class="btn btn-warning btn-lg next" id="'.$next_id.'" '.$if_next_disable.'>Next</button>
				  	</div>
				  	<br /><br />';
			}

			echo $output;

	}
	}
}

?>
