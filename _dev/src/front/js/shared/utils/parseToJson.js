const parseToJson = (data) => {
  let parsedData = JSON.parse(data);

  if (typeof parsedData === 'string') {
    parsedData = parseToJson(parsedData);
  }

  return parsedData;
};

export default parseToJson;
