import BlockTypes from 'js/block_types'
import BlockTypesView from './block_types_view'
import StudentView from './student_view'

import '../css/section.less'

export default BlockTypes.add({
  name: 'Section',
  views: {
    block_types: BlockTypesView,
    student: StudentView
  }
});
