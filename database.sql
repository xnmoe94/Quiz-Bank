CREATE TABLE `Teacher_table` (
  `admin_id` int(11) NOT NULL,
  `admin_email_address` varchar(150) NOT NULL,
  `admin_password` varchar(150) NOT NULL,
  `admin_created_on` datetime NOT NULL,
) ENGINE=MyISAM DEFAULT CHARSET=latin1;




CREATE TABLE `online_exam_table` (
  `exam_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `exam_title` varchar(250) NOT NULL,
  `exam_datetime` datetime NOT NULL,
  `exam_duration` varchar(30) NOT NULL,
  `total_question` int(5) NOT NULL,
  `Points_per_right_answer` varchar(30) NOT NULL,
  `Points_per_wrong_answer` varchar(30) NOT NULL,
  `online_exam_created_on` datetime NOT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




CREATE TABLE `Multiple_choose_option` (
  `option_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_number` int(2) NOT NULL,
  `option_title` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




CREATE TABLE `question_table` (
  `question_id` int(11) NOT NULL,
  `online_exam_id` int(11) NOT NULL,
  `question_title` text NOT NULL,
  `answer_option` enum('1','2','3','4') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
