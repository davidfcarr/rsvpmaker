import React from "react";

const Checkbox = ({ id, type, name, handleClick, isChecked, value }) => {
  return (
    <input
      id={id}
      name={name}
      type={type}
      onChange={handleClick}
      checked={isChecked}
      value={value}
    />
  );
};

export default Checkbox;