import BlockTypes from 'js/block_types'
import StudentView from './student_view'

import '../css/courseware.less'

export default BlockTypes.add({
  name: 'Courseware',
  views: {
    student: StudentView
  }
});
