/**
 * Import
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Local import
 */
// Components

// Style
import './messages.sass';

/**
 * Code
 */
class Messages extends React.Component {
  static propTypes = {
    messages: PropTypes.array/* Of(PropTypes.object.isRequired) */.isRequired,
  };

  // static defaultProps = {
  //   messages: [],
  // };

  componentDidUpdate() {
  // if (this.messageDiv.scrollTop + this.messageDiv.clientHeight === this.messageDiv.scrollHeight) {
    // this.messageDiv.scrollTop = this.messageDiv.scrollHeight;
    // }
    this.messageDiv.scrollTo(0, this.messageDiv.scrollHeight);
  }

  // saveRef = (domElement) => {
  //   this.node = domElement;
  // }

  render() {
    const { messages } = this.props;
    console.log(messages, 'ALL MESSAGES');
    return (
      <div
        ref={(element) => {
          this.messageDiv = element;
        }}
        className="messages"
      >
        {messages.map((message) => {
          const rollCrit = classNames(
            {
              'message-content-success': message.critic === 'success',
              'message-content-failed': message.critic === 'failed',
              'message-content-standart': message.critic === 'no',
            },
          );
          console.log(message, 'MESSAGE');
          return (
            <div className="message" key={message.id}>
              {console.log(message, 'mess in index.js')}
              {message.message
              && <p className="message-content">{message.author} {message.message ? message.message : message.dice}</p>}
              {message.diceValue
              && <p className="message-content">{message.author} a lancé un {message.diceValue} et a obtenu un <span className={rollCrit}> {message.dice} </span></p>}
            </div>
          );
        })}
      </div>
    );
  }
}


/**
 * Export
 */
export default Messages;
