import block_types from 'js/block_types'
import StudentView from './student_view'
import AuthorView from './author_view'

import '../css/code_block.less'

export default block_types.add({
  name: 'CodeBlock',

  content_block: true,

  views: {
    student: StudentView,
    author: AuthorView
  }
});
