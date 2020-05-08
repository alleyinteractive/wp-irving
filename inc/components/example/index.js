import React from 'react';
import PropTypes from 'prop-types';
import withThemes from '@irvingjs/styled/components/withThemes';
import themeMap from './themes/themeMap';

const Exammple = (props) => {
  const {
    children,
  } = props;

  const { Wrapper } = theme;

  return (
    <Wrapper>
      {children}
    </Wrapper>
  );
};

Exammple.defaultProps = {
  children: {},
};

Exammple.propTypes = {
  /**
   * Children of the component
   */
  children: PropTypes.node,
  /**
   * Theme (styles) to apply to the component.
   */
  theme: PropTypes.object.isRequired,
};

export default withThemes(themeMap)(Example);
