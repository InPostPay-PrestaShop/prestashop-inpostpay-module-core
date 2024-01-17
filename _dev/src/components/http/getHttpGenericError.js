/**
 * @return {Error}
 */
const getHttpGenericError = () => {
  const genericErrorMessage = window?.inpostizi_generic_http_error || 'Something went wrong. Please try again later.';

  return new Error(genericErrorMessage);
}

export default getHttpGenericError
