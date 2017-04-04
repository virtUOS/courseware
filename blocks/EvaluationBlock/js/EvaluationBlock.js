import block_types from 'js/block_types'
import StudentView from './student_view'

import '../css/evaluation_block.less'

export default block_types.add({
  name: 'EvaluationBlock',

  content_block: true,

  views: {
    student: StudentView
  }
});
