/**
 * Import
 */
import React from 'react';
import PropTypes from 'prop-types';
import axios from 'axios';

/**
 * Local import
 */
// Composants

// Styles et assets
import './sheet.sass';

/**
 * Code
 */
class Sheet extends React.Component {
  static propTypes = {
    charSheet: PropTypes.string.isRequired,
    sheetChange: PropTypes.func.isRequired,
    sheetId: PropTypes.string.isRequired,
  }

  componentDidMount() {
    console.log('Sheet loaded');
  }

  handleChange = (evt) => {
    const { sheetChange } = this.props;
    // Je recup la valeur du champ
    const { value } = evt.target;
    // passer la valeur vers le state => disptach avec une action capable de prendre cette valeur
    sheetChange(value);
  }

  focusLeave = (evt) => {
    console.log(evt.target.value);
    const { sheetId } = this.props;
    axios.post(`/charactersheet/${sheetId}`, evt.target.value)
      .then((response) => {
        console.log(response);
      })
      .catch((error) => {
        console.log(error);
      });
  }

  render() {
    const { charSheet } = this.props;
    return (
      <div className="sheet">
        <p className="sheet-name">Votre Nom</p>
        <textarea
          id="sheet-id"
          type="text"
          className="sheet-character"
          placeholder="feuille personnage"
          onChange={this.handleChange}
          value={charSheet}
          onBlur={this.focusLeave}
        />
      </div>
    );
  }
}

/**
 * Export
 */
export default Sheet;
